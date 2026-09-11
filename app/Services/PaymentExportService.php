<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PaymentExportService
{
    public function exportPaymentsWithFilters(array $filters): array
    {
        $query = Payment::query()
            ->leftJoin('clients', 'payments.client_id', '=', 'clients.user_id')
            ->leftJoin('rentals', 'payments.rental_id', '=', 'rentals.id')
            ->select([
                'payments.id',
                'payments.month',
                'payments.year',
                'payments.status',
                'payments.payment_type',
                'payments.article',
                'payments.purpose',
                'payments.total_amount',
                'payments.paid_amount',
                'payments.generated_at',
                'payments.paid_at',
                'payments.created_at',
                'clients.name        as client_name',
                'clients.phone_number as client_phone',
                'clients.username    as client_username',
                'clients.telegram_id as client_telegram_id',
                'payments.client_id  as client_id',
                'rentals.id          as rental_id',
                'rentals.status      as rental_status',
                'rentals.start_date  as rental_start_date',
            ]);

        $this->applyPaymentFilters($query, $filters);

        $rows = $query->get();

        // ⬇⬇⬇ Обогащаем ФИО через кастомные поля клиента
        $rows = $this->addClientFullNames($rows);

        $totalAmount    = (float) $rows->sum('total_amount');
        $totalPaid      = (float) $rows->sum('paid_amount');
        $totalRemaining = $totalAmount - $totalPaid;

        $fileName = 'payments_' . date('Y-m-d_His') . '.xlsx';
        $dir      = storage_path('app/exports');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $filePath = $dir . DIRECTORY_SEPARATOR . $fileName;

        $this->buildSpreadsheet($rows->toArray(), $totalAmount, $totalPaid, $totalRemaining)
            ->save($filePath);

        return [
            'name'            => $fileName,
            'path'            => $filePath,
            'count'           => $rows->count(),
            'total_amount'    => $totalAmount,
            'total_paid'      => $totalPaid,
            'total_remaining' => $totalRemaining,
        ];
    }

    public function applyPaymentFilters($query, array $filters): void
    {
        // ... без изменений (оставляем как у тебя)
        if (!empty($filters['status'])) {
            $status = $filters['status'];
            if (is_array($status)) {
                $query->whereIn('payments.status', $status);
            } elseif (strpos($status, ',') !== false) {
                $list = array_filter(array_map('trim', explode(',', $status)));
                $query->whereIn('payments.status', $list);
            } else {
                $query->where('payments.status', $status);
            }
        }

        if (!empty($filters['rental_status'])) {
            $query->where('rentals.status', $filters['rental_status']);
        }
        if (!empty($filters['year'])) {
            $query->where('payments.year', (int) $filters['year']);
        }
        if (!empty($filters['month'])) {
            $query->where('payments.month', (int) $filters['month']);
        }
        if (!empty($filters['payment_type'])) {
            $query->where('payments.payment_type', $filters['payment_type']);
        }
        if (!empty($filters['article'])) {
            $query->where('payments.article', $filters['article']);
        }
        if (!empty($filters['rental_id'])) {
            $query->where('payments.rental_id', $filters['rental_id']);
        }
        if (!empty($filters['client_id'])) {
            $query->where('payments.client_id', $filters['client_id']);
        }

        if (!empty($filters['client'])) {
            $search = '%' . $filters['client'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('clients.name', 'like', $search)
                  ->orWhere('clients.phone_number', 'like', $search)
                  ->orWhere('clients.username', 'like', $search)
                  ->orWhere('clients.telegram_id', 'like', $search);
            });
        }

        if (!empty($filters['generated_date_from'])) {
            $query->where('payments.generated_at', '>=', $filters['generated_date_from'] . ' 00:00:00');
        }
        if (!empty($filters['generated_date_to'])) {
            $query->where('payments.generated_at', '<=', $filters['generated_date_to'] . ' 23:59:59');
        }
        if (!empty($filters['paid_date_from'])) {
            $query->where('payments.paid_at', '>=', $filters['paid_date_from'] . ' 00:00:00');
        }
        if (!empty($filters['paid_date_to'])) {
            $query->where('payments.paid_at', '<=', $filters['paid_date_to'] . ' 23:59:59');
        }

        if (isset($filters['total_amount_min']) && $filters['total_amount_min'] !== '') {
            $query->where('payments.total_amount', '>=', (float) $filters['total_amount_min']);
        }
        if (isset($filters['total_amount_max']) && $filters['total_amount_max'] !== '') {
            $query->where('payments.total_amount', '<=', (float) $filters['total_amount_max']);
        }
        if (isset($filters['paid_amount_min']) && $filters['paid_amount_min'] !== '') {
            $query->where('payments.paid_amount', '>=', (float) $filters['paid_amount_min']);
        }
        if (isset($filters['paid_amount_max']) && $filters['paid_amount_max'] !== '') {
            $query->where('payments.paid_amount', '<=', (float) $filters['paid_amount_max']);
        }

        $allowedOrderBy = [
            'payments.created_at', 'payments.generated_at', 'payments.paid_at',
            'payments.total_amount', 'payments.paid_amount',
            'payments.year', 'payments.month', 'clients.name',
        ];
        $orderBy  = in_array($filters['order_by'] ?? '', $allowedOrderBy, true)
            ? $filters['order_by']
            : 'payments.created_at';
        $orderDir = strtolower($filters['order_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($orderBy, $orderDir);
    }

    /**
     * Добавляем ФИО клиента через custom_client_fields.
     * Логика 1:1 как в ExportService::addClientCustomFieldsToTransactions.
     */
    private function addClientFullNames($rows)
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        // ID клиентов из выборки (payments.client_id = clients.user_id)
        $clientIds = $rows->pluck('client_id')->filter()->unique()->values();

        $customFields = DB::table('custom_client_fields')
            ->whereIn('client_id', $clientIds)
            ->get()
            ->groupBy('client_id');

        foreach ($rows as $row) {
            $clientId = $row->client_id;

            // ⚠️ инициализируем ПУСТОЙ строкой, чтобы ФИО реально собиралось
            $row->client_full_name = '';

            if (isset($customFields[$clientId])) {
                $fullNameParts = [];
                $plainFullName = null;

                foreach ($customFields[$clientId] as $customField) {
                    $fieldName  = strtolower($customField->field_name);
                    $fieldValue = $customField->field_value;

                    if (in_array($fieldName, ['full_name', 'фио', 'fio'], true) && !empty($fieldValue)) {
                        $plainFullName = $fieldValue;
                    } elseif (in_array($fieldName, ['first_name', 'имя', 'name'], true) && !empty($fieldValue)) {
                        $fullNameParts['first_name'] = $fieldValue;
                    } elseif (in_array($fieldName, ['last_name', 'фамилия', 'surname'], true) && !empty($fieldValue)) {
                        $fullNameParts['last_name'] = $fieldValue;
                    } elseif (in_array($fieldName, ['middle_name', 'отчество', 'patronymic'], true) && !empty($fieldValue)) {
                        $fullNameParts['middle_name'] = $fieldValue;
                    }
                }

                if ($plainFullName) {
                    $row->client_full_name = $plainFullName;
                } elseif (!empty($fullNameParts)) {
                    $fullName = '';
                    if (!empty($fullNameParts['last_name'])) {
                        $fullName .= $fullNameParts['last_name'];
                    }
                    if (!empty($fullNameParts['first_name'])) {
                        $fullName .= ($fullName ? ' ' : '') . $fullNameParts['first_name'];
                    }
                    if (!empty($fullNameParts['middle_name'])) {
                        $fullName .= ($fullName ? ' ' : '') . $fullNameParts['middle_name'];
                    }
                    $row->client_full_name = $fullName;
                }
            }

            // Fallback — стандартное имя из clients
            if (empty($row->client_full_name)) {
                $row->client_full_name = $row->client_name ?? '';
            }
        }

        return $rows;
    }

    protected function buildSpreadsheet(
        array $rows,
        float $totalAmount,
        float $totalPaid,
        float $totalRemaining
    ): Xlsx {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Платежи');

        $headers = [
            'ID',
            'Клиент',
            'Телефон',
            'Username',
            'Telegram ID',
            'Аренда',
            'Статус аренды',
            'Начало аренды',
            'Месяц',
            'Год',
            'Статья',
            'Назначение',
            'Тип оплаты',
            'Сумма',
            'Оплачено',
            'Остаток',
            'Статус',
            'Дата генерации',
            'Дата оплаты',
            'Создан',
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }

        $rowNum = 2;
        foreach ($rows as $row) {
            $remaining = (float) $row['total_amount'] - (float) $row['paid_amount'];

            $sheet->setCellValue([1,  $rowNum], $row['id']);
            $sheet->setCellValue([2,  $rowNum], $row['client_full_name'] ?? ($row['client_name'] ?? ''));
            $sheet->setCellValue([3,  $rowNum], $row['client_phone'] ?? '');
            $sheet->setCellValue([4,  $rowNum], $row['client_username'] ?? '');
            $sheet->setCellValue([5,  $rowNum], $row['client_telegram_id'] ?? '');
            $sheet->setCellValue([6,  $rowNum], $row['rental_id'] ?? '');
            $sheet->setCellValue([7,  $rowNum], $this->translateRentalStatus($row['rental_status'] ?? ''));
            $sheet->setCellValue([8,  $rowNum], $this->formatDate($row['rental_start_date'] ?? null));
            $sheet->setCellValue([9,  $rowNum], $this->translateMonth($row['month'] ?? null));
            $sheet->setCellValue([10, $rowNum], $row['year'] ?? '');
            $sheet->setCellValue([11, $rowNum], $this->translateArticle($row['article'] ?? ''));
            $sheet->setCellValue([12, $rowNum], $row['purpose'] ?? '');
            $sheet->setCellValue([13, $rowNum], $this->translatePaymentType($row['payment_type'] ?? ''));
            $sheet->setCellValue([14, $rowNum], (float) $row['total_amount']);
            $sheet->setCellValue([15, $rowNum], (float) $row['paid_amount']);
            $sheet->setCellValue([16, $rowNum], $remaining);
            $sheet->setCellValue([17, $rowNum], $this->translatePaymentStatus($row['status'] ?? ''));
            $sheet->setCellValue([18, $rowNum], $this->formatDate($row['generated_at'] ?? null));
            $sheet->setCellValue([19, $rowNum], $this->formatDate($row['paid_at'] ?? null));
            $sheet->setCellValue([20, $rowNum], $this->formatDate($row['created_at'] ?? null));
            $rowNum++;
        }

        // Итоговая строка
        $sheet->setCellValue([13, $rowNum], 'ИТОГО:');
        $sheet->setCellValue([14, $rowNum], $totalAmount);
        $sheet->setCellValue([15, $rowNum], $totalPaid);
        $sheet->setCellValue([16, $rowNum], $totalRemaining);

        // Форматирование шапки
        $lastCol     = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A1:{$lastCol}1";

        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E5E7EB');
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Числовой формат для сумм (колонки N, O, P)
        $lastRow = $rowNum;
        $sheet->getStyle("N2:P{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // Автоширина
        for ($i = 1; $i <= count($headers); $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        // Границы
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Жирная итоговая строка
        $sheet->getStyle("M{$lastRow}:P{$lastRow}")->getFont()->setBold(true);

        return new Xlsx($spreadsheet);
    }

    // ============================================================
    //  Переводы значений на русский
    // ============================================================

    private function translatePaymentStatus($status): string
{
    $key = strtolower((string) $status);

    return [
        'unpaid'         => 'Не оплачен',
        'partially_paid' => 'Частично оплачен',
        'paid'           => 'Оплачен',
    ][$key] ?? (string) $status;
}

private function translatePaymentType($type): string
{
    $key = strtolower((string) $type);

    return [
        'cash'      => 'Наличные',
        'cashless'  => 'Безналичные',
        'mixed'     => 'Смешанный',
        'corporate' => 'Корпоративный',
    ][$key] ?? (string) $type;
}

private function translateArticle($article): string
{
    $key = strtolower((string) $article);

    return [
        'bike_rental' => 'Аренда велосипеда',
        'bike_repair' => 'Ремонт велосипеда',
    ][$key] ?? (string) $article;
}

/**
 * Месяц у платежей — английское название в нижнем регистре.
 * Оставляем также поддержку чисел 1..12 на случай старых записей.
 */
private function translateMonth($month): string
{
    if ($month === null || $month === '') {
        return '';
    }

    if (is_numeric($month)) {
        return [
            1  => 'Январь',   2  => 'Февраль', 3  => 'Март',
            4  => 'Апрель',   5  => 'Май',     6  => 'Июнь',
            7  => 'Июль',     8  => 'Август',  9  => 'Сентябрь',
            10 => 'Октябрь',  11 => 'Ноябрь',  12 => 'Декабрь',
        ][(int) $month] ?? (string) $month;
    }

    $key = strtolower(trim((string) $month));

    return [
        'january'   => 'Январь',
        'february'  => 'Февраль',
        'march'     => 'Март',
        'april'     => 'Апрель',
        'may'       => 'Май',
        'june'      => 'Июнь',
        'july'      => 'Июль',
        'august'    => 'Август',
        'september' => 'Сентябрь',
        'october'   => 'Октябрь',
        'november'  => 'Ноябрь',
        'december'  => 'Декабрь',

        // короткие формы на всякий случай
        'jan' => 'Январь', 'feb' => 'Февраль', 'mar' => 'Март',
        'apr' => 'Апрель', 'jun' => 'Июнь',    'jul' => 'Июль',
        'aug' => 'Август', 'sep' => 'Сентябрь','sept'=> 'Сентябрь',
        'oct' => 'Октябрь','nov' => 'Ноябрь',  'dec' => 'Декабрь',
    ][$key] ?? (string) $month;
}
    private function translateRentalStatus($status): string
    {
        return [
            'active'    => 'Активна',
            'completed' => 'Завершена',
            'cancelled' => 'Отменена',
            'pending'   => 'Ожидание',
        ][$status] ?? (string) $status;
    }

    // ============================================================
    //  Форматирование дат
    // ============================================================

    /**
     * Приводит дату к виду d.m.Y H:i:s.
     * Для дат без времени (start_date и т.п.) вернёт d.m.Y.
     */
    private function formatDate($value, bool $withTime = true): string
    {
        if (empty($value) || $value === '0000-00-00 00:00:00' || $value === '0000-00-00') {
            return '';
        }

        try {
            $ts = is_numeric($value) ? (int) $value : strtotime((string) $value);
            if ($ts === false) {
                return (string) $value;
            }

            // Если время 00:00:00 — считаем, что это чистая дата
            $hasTime = date('H:i:s', $ts) !== '00:00:00';

            return date($withTime && $hasTime ? 'd.m.Y H:i:s' : 'd.m.Y', $ts);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}