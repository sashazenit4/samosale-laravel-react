<?php

namespace App\Http\Controllers;

use App\Services\PaymentExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentExportController extends Controller
{
    protected PaymentExportService $exportService;

    public function __construct(PaymentExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * API: получить доступные фильтры для платежей
     */
    public function getFilters(): JsonResponse
    {
        $filters = [
            'status' => [
                'name'    => 'status',
                'label'   => 'Статус',
                'type'    => 'select',
                'options' => [
                    ['value' => 'unpaid',         'label' => 'Не оплачен'],
                    ['value' => 'partially_paid', 'label' => 'Частично оплачен'],
                    ['value' => 'paid',           'label' => 'Оплачен'],
                    ['value' => '',               'label' => 'Все статусы'],
                ],
            ],
            'payment_type' => [
                'name'    => 'payment_type',
                'label'   => 'Тип оплаты',
                'type'    => 'select',
                'options' => [
                    ['value' => 'cash',     'label' => 'Наличные'],
                    ['value' => 'card',     'label' => 'Карта'],
                    ['value' => 'transfer', 'label' => 'Перевод'],
                    ['value' => '',         'label' => 'Все типы'],
                ],
            ],
            'article' => [
                'name'    => 'article',
                'label'   => 'Статья',
                'type'    => 'select',
                'options' => [
                    ['value' => 'rent',       'label' => 'Аренда'],
                    ['value' => 'utilities',  'label' => 'Коммунальные'],
                    ['value' => 'other',      'label' => 'Прочее'],
                    ['value' => '',           'label' => 'Все статьи'],
                ],
            ],
            'year' => [
                'name'  => 'year',
                'label' => 'Год',
                'type'  => 'number',
                'min'   => '2000',
                'max'   => '2100',
            ],
            'month' => [
                'name'    => 'month',
                'label'   => 'Месяц',
                'type'    => 'select',
                'options' => [
                    ['value' => '1',  'label' => 'Январь'],
                    ['value' => '2',  'label' => 'Февраль'],
                    ['value' => '3',  'label' => 'Март'],
                    ['value' => '4',  'label' => 'Апрель'],
                    ['value' => '5',  'label' => 'Май'],
                    ['value' => '6',  'label' => 'Июнь'],
                    ['value' => '7',  'label' => 'Июль'],
                    ['value' => '8',  'label' => 'Август'],
                    ['value' => '9',  'label' => 'Сентябрь'],
                    ['value' => '10', 'label' => 'Октябрь'],
                    ['value' => '11', 'label' => 'Ноябрь'],
                    ['value' => '12', 'label' => 'Декабрь'],
                    ['value' => '',   'label' => 'Все месяцы'],
                ],
            ],
            'client' => [
                'name'        => 'client',
                'label'       => 'Клиент (имя или телефон)',
                'type'        => 'text',
                'placeholder' => 'Введите имя или номер телефона',
            ],
            'generated_date_from' => [
                'name'  => 'generated_date_from',
                'label' => 'Дата генерации от',
                'type'  => 'date',
            ],
            'generated_date_to' => [
                'name'  => 'generated_date_to',
                'label' => 'Дата генерации до',
                'type'  => 'date',
            ],
            'paid_date_from' => [
                'name'  => 'paid_date_from',
                'label' => 'Дата оплаты от',
                'type'  => 'date',
            ],
            'paid_date_to' => [
                'name'  => 'paid_date_to',
                'label' => 'Дата оплаты до',
                'type'  => 'date',
            ],
            'total_amount_min' => [
                'name'  => 'total_amount_min',
                'label' => 'Сумма от',
                'type'  => 'number',
                'step'  => '0.01',
                'min'   => '0',
            ],
            'total_amount_max' => [
                'name'  => 'total_amount_max',
                'label' => 'Сумма до',
                'type'  => 'number',
                'step'  => '0.01',
                'min'   => '0',
            ],
            'paid_amount_min' => [
                'name'  => 'paid_amount_min',
                'label' => 'Оплачено от',
                'type'  => 'number',
                'step'  => '0.01',
                'min'   => '0',
            ],
            'paid_amount_max' => [
                'name'  => 'paid_amount_max',
                'label' => 'Оплачено до',
                'type'  => 'number',
                'step'  => '0.01',
                'min'   => '0',
            ],
            'order_by' => [
                'name'    => 'order_by',
                'label'   => 'Сортировка по',
                'type'    => 'select',
                'options' => [
                    ['value' => 'payments.created_at',   'label' => 'Дате создания'],
                    ['value' => 'payments.generated_at', 'label' => 'Дате генерации'],
                    ['value' => 'payments.paid_at',      'label' => 'Дате оплаты'],
                    ['value' => 'payments.total_amount', 'label' => 'Сумме'],
                    ['value' => 'payments.paid_amount',  'label' => 'Оплаченной сумме'],
                    ['value' => 'clients.name',          'label' => 'Имени клиента'],
                ],
            ],
            'order_dir' => [
                'name'    => 'order_dir',
                'label'   => 'Направление сортировки',
                'type'    => 'select',
                'options' => [
                    ['value' => 'desc', 'label' => 'По убыванию'],
                    ['value' => 'asc',  'label' => 'По возрастанию'],
                ],
            ],
        ];

        return response()->json([
            'success'        => true,
            'filters'        => $filters,
            'default_values' => [
                'order_by'  => 'payments.created_at',
                'order_dir' => 'desc',
            ],
        ]);
    }

    /**
     * API: экспорт платежей с фильтрацией
     */
    public function exportPayments(Request $request): JsonResponse
    {
        try {
            $filters = $this->cleanFilters($request->all());

            $result = $this->exportService->exportPaymentsWithFilters($filters);

            return response()->json([
                'success' => true,
                'message' => 'Платежи успешно экспортированы',
                'data'    => [
                    'file_name'            => $result['name'],
                    'row_count'            => $result['count'],
                    'total_amount'         => $result['total_amount'],
                    'total_paid'           => $result['total_paid'],
                    'total_remaining'      => $result['total_remaining'],
                    'download_url'         => url('/api/payments/export/download/' . basename($result['path'])),
                    'direct_download_url'  => url('/api/payments/export/direct?' . http_build_query($filters)),
                ],
                'summary' => [
                    'total_records'   => $result['count'],
                    'total_amount'    => number_format($result['total_amount'], 2, '.', ' '),
                    'total_paid'      => number_format($result['total_paid'], 2, '.', ' '),
                    'total_remaining' => number_format($result['total_remaining'], 2, '.', ' '),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'trace'   => config('app.debug') ? $e->getTraceAsString() : null,
            ], 400);
        }
    }

    /**
     * Прямое скачивание файла платежей
     */
    public function directExport(Request $request): BinaryFileResponse
    {
        try {
            $filters = $this->cleanFilters($request->all());
            $result  = $this->exportService->exportPaymentsWithFilters($filters);

            return response()->download(
                $result['path'],
                $result['name'],
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // Файл с ошибкой
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Ошибка экспорта платежей');
            $sheet->setCellValue('A2', $e->getMessage());

            $fileName = 'payments_error_' . date('Y-m-d_His') . '.xlsx';
            $dir      = storage_path('app/exports');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $filePath = $dir . DIRECTORY_SEPARATOR . $fileName;

            (new Xlsx($spreadsheet))->save($filePath);

            return response()->download($filePath, 'error_export.xlsx')
                ->deleteFileAfterSend(true);
        }
    }

    /**
     * Скачивание файла по имени
     */
    public function downloadFile(string $filename): BinaryFileResponse
    {
        // Защита от path traversal
        $filename = basename($filename);
        $filePath = storage_path('app/exports/' . $filename);

        if (!file_exists($filePath)) {
            abort(404, 'Файл не найден');
        }

        return response()->download(
            $filePath,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    /**
     * Веб-форма экспорта
     */
    public function showForm()
    {
        return view('payments.export-form');
    }

    /**
     * Статистика по платежам (с учётом фильтров)
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $filters = $this->cleanFilters($request->all());

            $base = DB::table('payments')
                ->leftJoin('clients', 'payments.client_id', '=', 'clients.user_id');

            $this->exportService->applyPaymentFilters($base, $filters);

            // Клонируем запрос для агрегатов
            $aggregate = (clone $base)->selectRaw('
                COUNT(*) as total_count,
                COALESCE(SUM(payments.total_amount), 0) as total_amount,
                COALESCE(SUM(payments.paid_amount), 0) as total_paid,
                COALESCE(SUM(payments.total_amount - payments.paid_amount), 0) as total_remaining,
                COUNT(CASE WHEN payments.status = "paid" THEN 1 END) as paid_count,
                COUNT(CASE WHEN payments.status = "partially_paid" THEN 1 END) as partially_paid_count,
                COUNT(CASE WHEN payments.status = "unpaid" THEN 1 END) as unpaid_count
            ')->first();

            // Распределение по типам оплаты
            $typeStats = (clone $base)
                ->select('payments.payment_type', DB::raw('COUNT(*) as count'))
                ->groupBy('payments.payment_type')
                ->get()
                ->mapWithKeys(fn ($i) => [$i->payment_type => (int) $i->count]);

            // Распределение по статьям
            $articleStats = (clone $base)
                ->select('payments.article', DB::raw('COUNT(*) as count'))
                ->groupBy('payments.article')
                ->get()
                ->mapWithKeys(fn ($i) => [$i->article => (int) $i->count]);

            // Последние 5 платежей
            $recent = DB::table('payments')
                ->leftJoin('clients', 'payments.client_id', '=', 'clients.user_id')
                ->select(
                    'payments.id',
                    'payments.total_amount',
                    'payments.paid_amount',
                    'payments.status',
                    'payments.year',
                    'payments.month',
                    'clients.name as client_name',
                    'payments.created_at'
                )
                ->orderBy('payments.created_at', 'desc')
                ->limit(5)
                ->get();

            $avg = $aggregate->total_count > 0
                ? $aggregate->total_amount / $aggregate->total_count
                : 0;

            return response()->json([
                'success' => true,
                'stats'   => [
                    'total_payments'      => (int) $aggregate->total_count,
                    'total_amount'        => (float) $aggregate->total_amount,
                    'total_paid'          => (float) $aggregate->total_paid,
                    'total_remaining'     => (float) $aggregate->total_remaining,
                    'paid_count'          => (int) $aggregate->paid_count,
                    'partially_paid_count'=> (int) $aggregate->partially_paid_count,
                    'unpaid_count'        => (int) $aggregate->unpaid_count,
                    'average_payment'     => (float) $avg,
                    'type_distribution'   => $typeStats,
                    'article_distribution'=> $articleStats,
                ],
                'recent_payments' => $recent,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Удаление пустых значений из фильтров.
     */
    protected function cleanFilters(array $filters): array
    {
        return array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });
    }
}