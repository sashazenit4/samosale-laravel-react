<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExportService
{
    private $tableModels = [
        'bikes' => \App\Models\Bike::class,
        'bonus_operations' => \App\Models\BonusOperation::class,
        'clients' => \App\Models\Client::class,
        'custom_client_fields' => \App\Models\CustomClientField::class,
        'equipment' => \App\Models\Equipment::class,
        'payments' => \App\Models\Payment::class,
        'rentals' => \App\Models\Rental::class,
        'tariffs' => \App\Models\Tariff::class,
        'transactions' => \App\Models\Transaction::class,
    ];

    // Определяем первичные ключи для каждой таблицы
    private $primaryKeys = [
        'bikes' => 'id',
        'bonus_operations' => 'id',
        'clients' => 'user_id',  // Особый случай
        'custom_client_fields' => 'id',
        'equipment' => 'id',
        'payments' => 'id',
        'rentals' => 'id',
        'tariffs' => 'id',
        'transactions' => 'id',
    ];

    // Определяем связи между таблицами с учетом их структуры
    private $relationsMap = [
        'bonus_operations' => [
            'client_id' => [
                'table' => 'clients',
                'foreign_key' => 'client_id',
                'primary_key' => 'user_id', // У клиентов первичный ключ user_id
                'display' => 'name'
            ],
            'transaction_id' => [
                'table' => 'transactions',
                'foreign_key' => 'transaction_id',
                'primary_key' => 'id',
                'display' => 'id'
            ],
        ],
        'payments' => [
            'client_id' => [
                'table' => 'clients',
                'foreign_key' => 'client_id',
                'primary_key' => 'user_id',
                'display' => 'name'
            ],
            'rental_id' => [
                'table' => 'rentals',
                'foreign_key' => 'rental_id',
                'primary_key' => 'id',
                'display' => 'id'
            ],
        ],
        'rentals' => [
            'client_id' => [
                'table' => 'clients',
                'foreign_key' => 'client_id',
                'primary_key' => 'user_id',
                'display' => 'name'
            ],
            'bike_id' => [
                'table' => 'bikes',
                'foreign_key' => 'bike_id',
                'primary_key' => 'id',
                'display' => 'bike_number'
            ],
            'tariff_id' => [
                'table' => 'tariffs',
                'foreign_key' => 'tariff_id',
                'primary_key' => 'id',
                'display' => 'program'
            ],
        ],
        'transactions' => [
            'payment_id' => [
                'table' => 'payments',
                'foreign_key' => 'payment_id',
                'primary_key' => 'id',
                'display' => 'id'
            ],
            'client_id' => [
                'table' => 'clients',
                'foreign_key' => 'client_id',
                'primary_key' => 'user_id',
                'display' => 'name'
            ],
        ],
        'custom_client_fields' => [
            'client_id' => [
                'table' => 'clients',
                'foreign_key' => 'client_id',
                'primary_key' => 'user_id',
                'display' => 'name'
            ],
        ],
    ];

    public function exportToExcel(string $tableName, array $filters = [])
    {
        if (!Schema::hasTable($tableName)) {
            throw new \Exception("Table {$tableName} does not exist");
        }

        $data = $this->getTableData($tableName, $filters);

        if ($tableName === 'clients') {
            $data = $this->addCustomClientFields($data);
        }

        return $this->generateExcel($tableName, $data);
    }

    private function getTableData(string $tableName, array $filters = [])
    {
        $primaryKey = $this->getPrimaryKey($tableName);
        $query = DB::table($tableName);

        // Apply filters if any
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '' && Schema::hasColumn($tableName, $field)) {
                $query->where($field, 'like', "%{$value}%");
            }
        }

        // Добавляем первичный ключ в SELECT, если его нет
        $columns = Schema::getColumnListing($tableName);
        if (!in_array($primaryKey, $columns) && $primaryKey !== 'id') {
            $query->addSelect($columns);
        }

        $data = $query->get();

        // Process relations if table has them
        if (isset($this->relationsMap[$tableName])) {
            $data = $this->resolveRelations($tableName, $data);
        }

        return $data;
    }

    private function resolveRelations(string $tableName, $data)
    {
        foreach ($this->relationsMap[$tableName] as $foreignKey => $relationConfig) {
            $relatedTable = $relationConfig['table'];
            $foreignKeyColumn = $relationConfig['foreign_key'];
            $primaryKey = $relationConfig['primary_key'];
            $displayField = $relationConfig['display'];

            // Get all foreign keys from data
            $foreignKeys = $data->pluck($foreignKeyColumn)->filter()->unique();

            if ($foreignKeys->isNotEmpty()) {
                // Fetch related data with correct primary key
                $relatedData = DB::table($relatedTable)
                    ->whereIn($primaryKey, $foreignKeys)
                    ->get()
                    ->keyBy($primaryKey);

                // Add relation to each item
                foreach ($data as $item) {
                    $foreignKeyValue = $item->$foreignKeyColumn;
                    if ($foreignKeyValue && isset($relatedData[$foreignKeyValue])) {
                        $item->{$foreignKeyColumn . '_display'} = $relatedData[$foreignKeyValue]->$displayField;
                    } else {
                        $item->{$foreignKeyColumn . '_display'} = null;
                    }
                }
            }
        }

        return $data;
    }

    private function addCustomClientFields($clientsData)
    {
        if ($clientsData->isEmpty()) {
            return $clientsData;
        }

        // Get all client IDs (user_id)
        $clientIds = $clientsData->pluck('user_id');

        // Fetch all custom fields for these clients
        $customFields = DB::table('custom_client_fields')
            ->whereIn('client_id', $clientIds)
            ->get()
            ->groupBy('client_id');

        // Get unique field names for headers
        $uniqueFieldNames = DB::table('custom_client_fields')
            ->select('field_name')
            ->distinct()
            ->pluck('field_name');

        foreach ($clientsData as $client) {
            $clientId = $client->user_id;

            if (isset($customFields[$clientId])) {
                foreach ($customFields[$clientId] as $customField) {
                    $client->{$customField->field_name} = $customField->field_value;
                }
            }

            // Add empty values for fields this client doesn't have
            foreach ($uniqueFieldNames as $fieldName) {
                if (!isset($client->$fieldName)) {
                    $client->$fieldName = null;
                }
            }
        }

        return $clientsData;
    }

    private function generateExcel(string $tableName, $data)
    {
        if ($data->isEmpty()) {
            throw new \Exception("No data found for table {$tableName}");
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($tableName);

        // Get headers
        $firstRow = (array) $data->first();
        $headers = array_keys($firstRow);

        // Write headers
        foreach ($headers as $colIndex => $header) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($columnLetter . '1', $this->formatHeader($header, $tableName));
            $sheet->getStyle($columnLetter . '1')->getFont()->setBold(true);
        }

        // Write data
        $rowIndex = 2;
        foreach ($data as $row) {
            $rowArray = (array) $row;
            foreach ($headers as $colIndex => $header) {
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
                $value = $rowArray[$header] ?? '';

                // Convert dates to readable format
                if ($this->isDateField($header)) {
                    $value = $this->formatDate($value);
                }

                // Format decimal values
                if ($this->isDecimalField($header, $tableName)) {
                    $value = $this->formatDecimal($value);
                }

                $sheet->setCellValue($columnLetter . $rowIndex, $value);
            }
            $rowIndex++;
        }

        // Auto size columns
        $columnCount = count($headers);
        for ($i = 1; $i <= $columnCount; $i++) {
            $columnLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        // Apply styling for header
        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex($columnCount) . '1')
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFE0E0E0');

        // Add borders
        $lastRow = $rowIndex - 1;
        if ($lastRow >= 1) {
            $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex($columnCount) . $lastRow)
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }

        // Create temporary file
        $fileName = $tableName . '_export_' . date('Y-m-d_His') . '.xlsx';
        $filePath = storage_path('app/exports/' . $fileName);

        // Ensure directory exists
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return [
            'path' => $filePath,
            'name' => $fileName,
            'count' => $data->count()
        ];
    }

    private function getPrimaryKey(string $tableName): string
    {
        return $this->primaryKeys[$tableName] ?? 'id';
    }

    private function formatHeader(string $header, string $tableName = ''): string
    {
        // Format header names
        $header = str_replace('_', ' ', $header);

        // Remove _display suffix for display
        if (str_ends_with($header, ' display')) {
            $header = str_replace(' display', '', $header) . ' (связанное)';
        }

        // Special formatting for common fields
        $replacements = [
            'id' => 'ID',
            'user id' => 'ID пользователя',
            'client id' => 'ID клиента',
            'bike id' => 'ID велосипеда',
            'tariff id' => 'ID тарифа',
            'transaction id' => 'ID транзакции',
            'payment id' => 'ID платежа',
            'rental id' => 'ID аренды',
            'created at' => 'Дата создания',
            'updated at' => 'Дата обновления',
            'start date' => 'Дата начала',
            'end date' => 'Дата окончания',
            'planned end date' => 'Плановая дата окончания',
            'actual end date' => 'Фактическая дата окончания',
            'paid at' => 'Дата оплаты',
            'phone number' => 'Номер телефона',
            'registration date' => 'Дата регистрации',
            'referral code' => 'Реферальный код',
            'referred by' => 'Приглашен пользователем',
            'bonus balance' => 'Бонусный баланс',
            'balance' => 'Баланс',
            'bike number' => 'Номер велосипеда',
            'frame number' => 'Номер рамы',
            'total amount' => 'Общая сумма',
            'paid amount' => 'Оплаченная сумма',
            'total cost' => 'Общая стоимость',
            'refund amount' => 'Сумма возврата',
            'bonus deduct amount' => 'Сумма списанных бонусов',
            'qr code id' => 'ID QR кода',
            'qr code url' => 'URL QR кода',
            'expires at' => 'Истекает',
            'bank transaction id' => 'ID банковской транзакции',
            'bank request' => 'Запрос банку',
            'bank response' => 'Ответ банка',
            'battery capacity' => 'Емкость батареи',
            'batteries count' => 'Количество батарей',
            'telegram id' => 'Telegram ID',
            'property 1' => 'Свойство 1',
            'property 2' => 'Свойство 2',
            'property 3' => 'Свойство 3',
            'property 4' => 'Свойство 4',
            'property 5' => 'Свойство 5',
            'property 6' => 'Свойство 6',
            'property 7' => 'Свойство 7',
            'property 8' => 'Свойство 8',
            'property 9' => 'Свойство 9',
            'property 10' => 'Свойство 10',
            'metadata' => 'Метаданные',
        ];

        $lowerHeader = strtolower($header);
        if (isset($replacements[$lowerHeader])) {
            return $replacements[$lowerHeader];
        }

        return ucwords($header);
    }

    private function isDateField(string $fieldName): bool
    {
        $dateFields = [
            'created_at', 'updated_at', 'start_date', 'end_date', 'paid_at',
            'registration_date', 'generated_at', 'expires_at', 'planned_end_date',
            'actual_end_date'
        ];

        return in_array($fieldName, $dateFields);
    }

    private function isDecimalField(string $fieldName, string $tableName): bool
    {
        $decimalFields = [
            'amount', 'balance', 'bonus_balance', 'total_amount', 'paid_amount',
            'total_cost', 'refund_amount', 'bonus_deduct_amount', 'price_month',
            'price_week1', 'price_week2', 'price_week3', 'price_week4'
        ];

        return in_array($fieldName, $decimalFields);
    }

    private function formatDate($value)
    {
        if (!$value || $value === '0000-00-00 00:00:00') {
            return '';
        }

        try {
            if ($value instanceof \DateTime) {
                return $value->format('d.m.Y H:i:s');
            }

            if (is_string($value)) {
                $timestamp = strtotime($value);
                if ($timestamp !== false) {
                    return date('d.m.Y H:i:s', $timestamp);
                }
            }

            return $value;
        } catch (\Exception $e) {
            return $value;
        }
    }

    private function formatDecimal($value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Remove unnecessary zeros
        $floatValue = (float) $value;
        if ($floatValue == (int) $floatValue) {
            return (int) $floatValue;
        }

        return number_format($floatValue, 2, '.', '');
    }

    public function getAvailableTables(): array
    {
        return array_keys($this->tableModels);
    }

    public function getTableStructure(string $tableName): array
    {
        if (!Schema::hasTable($tableName)) {
            throw new \Exception("Table {$tableName} does not exist");
        }

        return [
            'primary_key' => $this->getPrimaryKey($tableName),
            'columns' => Schema::getColumnListing($tableName),
            'has_relations' => isset($this->relationsMap[$tableName])
        ];
    }

    /**
     * Специальный метод для экспорта транзакций с расширенной фильтрацией
     */
    public function exportTransactionsWithFilters(array $filters = [])
    {
        $query = DB::table('transactions')
            ->join('clients', 'transactions.client_id', '=', 'clients.user_id')
            ->leftJoin('payments', 'transactions.payment_id', '=', 'payments.id')
            ->select(
                'transactions.*',
                'clients.name as client_name',
                'clients.phone_number as client_phone',
                'payments.month as payment_month',
                'payments.year as payment_year',
                'payments.total_amount as payment_total_amount'
            );

        // Применяем фильтры
        $this->applyTransactionFilters($query, $filters);

        $data = $query->get();

        // Дополнительная обработка данных
        $data = $this->enhanceTransactionData($data);

        return $this->generateTransactionExcel($data, $filters);
    }

    /**
     * Применение фильтров для транзакций
     */
    public function applyTransactionFilters($query, array $filters)
    {
        // Фильтр по статусу
        if (!empty($filters['status'])) {
            $query->where('transactions.status', $filters['status']);
        }

        // Фильтр по типу
        if (!empty($filters['type'])) {
            $query->where('transactions.type', $filters['type']);
        }

        // Фильтр по клиенту (имя или телефон)
        if (!empty($filters['client'])) {
            $query->where(function($q) use ($filters) {
                $q->where('clients.name', 'like', '%' . $filters['client'] . '%')
                    ->orWhere('clients.phone_number', 'like', '%' . $filters['client'] . '%');
            });
        }

        // Фильтр по дате создания (от)
        if (!empty($filters['date_from'])) {
            $query->whereDate('transactions.created_at', '>=', $filters['date_from']);
        }

        // Фильтр по дате создания (до)
        if (!empty($filters['date_to'])) {
            $query->whereDate('transactions.created_at', '<=', $filters['date_to']);
        }

        // Фильтр по дате оплаты (от)
        if (!empty($filters['paid_date_from'])) {
            $query->whereDate('transactions.paid_at', '>=', $filters['paid_date_from']);
        }

        // Фильтр по дате оплаты (до)
        if (!empty($filters['paid_date_to'])) {
            $query->whereDate('transactions.paid_at', '<=', $filters['paid_date_to']);
        }

        // Фильтр по сумме (мин)
        if (!empty($filters['amount_min'])) {
            $query->where('transactions.amount', '>=', $filters['amount_min']);
        }

        // Фильтр по сумме (макс)
        if (!empty($filters['amount_max'])) {
            $query->where('transactions.amount', '<=', $filters['amount_max']);
        }

        // Фильтр по ID банковской транзакции
        if (!empty($filters['bank_transaction_id'])) {
            $query->where('transactions.bank_transaction_id', 'like', '%' . $filters['bank_transaction_id'] . '%');
        }

        // Сортировка
        $orderBy = $filters['order_by'] ?? 'transactions.created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);
    }

    /**
     * Дополнительная обработка данных транзакций
     */
    private function enhanceTransactionData($data)
    {
        foreach ($data as $transaction) {
            // Форматируем статус на русском
            $transaction->status_display = $this->translateTransactionStatus($transaction->status);

            // Форматируем тип на русском
            $transaction->type_display = $this->translateTransactionType($transaction->type);

            // Форматируем суммы
            $transaction->amount_formatted = number_format($transaction->amount, 2, '.', ' ');
            $transaction->bonus_deduct_amount_formatted = number_format($transaction->bonus_deduct_amount, 2, '.', ' ');

            // Рассчитываем итоговую сумму с учетом бонусов
            $transaction->final_amount = $transaction->amount - $transaction->bonus_deduct_amount;
            $transaction->final_amount_formatted = number_format($transaction->final_amount, 2, '.', ' ');

            // Форматируем даты
            if ($transaction->created_at) {
                $transaction->created_date = date('d.m.Y', strtotime($transaction->created_at));
                $transaction->created_time = date('H:i:s', strtotime($transaction->created_at));
            }

            if ($transaction->paid_at) {
                $transaction->paid_date = date('d.m.Y', strtotime($transaction->paid_at));
                $transaction->paid_time = date('H:i:s', strtotime($transaction->paid_at));
            }

            if ($transaction->expires_at) {
                $transaction->expires_date = date('d.m.Y H:i', strtotime($transaction->expires_at));
            }
        }

        return $data;
    }

    /**
     * Генерация Excel для транзакций с улучшенным форматированием
     */
    private function generateTransactionExcel($data, array $filters = [])
    {
        if ($data->isEmpty()) {
            throw new \Exception("No transactions found with applied filters");
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Транзакции');

        // Добавляем информацию о фильтрах
        $rowIndex = 1;
        $sheet->setCellValue('A' . $rowIndex, 'Отчет по транзакциям');
        $sheet->getStyle('A' . $rowIndex)->getFont()->setBold(true)->setSize(14);

        $rowIndex++;
        $sheet->setCellValue('A' . $rowIndex, 'Сформировано: ' . date('d.m.Y H:i:s'));
        $rowIndex++;

        if (!empty($filters)) {
            $sheet->setCellValue('A' . $rowIndex, 'Примененные фильтры:');
            $sheet->getStyle('A' . $rowIndex)->getFont()->setBold(true);
            $rowIndex++;

            foreach ($filters as $key => $value) {
                if ($value) {
                    $displayKey = $this->formatTransactionFilterName($key);
                    $sheet->setCellValue('A' . $rowIndex, $displayKey . ':');
                    $sheet->setCellValue('B' . $rowIndex, $value);
                    $rowIndex++;
                }
            }
        }

        $rowIndex += 2; // Пропуск строк

        // Заголовки таблицы
        $headers = [
            'ID транзакции',
            'Дата создания',
            'Время создания',
            'Клиент',
            'Телефон клиента',
            'Сумма',
            'Списано бонусов',
            'Итоговая сумма',
            'Статус',
            'Тип',
            'ID банковской транзакции',
            'Дата оплаты',
            'Время оплаты',
            'ID платежа',
            'Месяц платежа',
            'Год платежа',
            'Сумма платежа',
            'Описание',
            'Срок действия QR',
            'Статус QR'
        ];

        $headerRow = $rowIndex;
        foreach ($headers as $colIndex => $header) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($columnLetter . $headerRow, $header);
            $sheet->getStyle($columnLetter . $headerRow)->getFont()->setBold(true);
        }

        $rowIndex++;

        // Данные
        foreach ($data as $transaction) {
            $sheet->setCellValue('A' . $rowIndex, $transaction->id);
            $sheet->setCellValue('B' . $rowIndex, $transaction->created_date ?? '');
            $sheet->setCellValue('C' . $rowIndex, $transaction->created_time ?? '');
            $sheet->setCellValue('D' . $rowIndex, $transaction->client_name ?? '');
            $sheet->setCellValue('E' . $rowIndex, $transaction->client_phone ?? '');
            $sheet->setCellValue('F' . $rowIndex, $transaction->amount_formatted);
            $sheet->setCellValue('G' . $rowIndex, $transaction->bonus_deduct_amount_formatted);
            $sheet->setCellValue('H' . $rowIndex, $transaction->final_amount_formatted);
            $sheet->setCellValue('I' . $rowIndex, $transaction->status_display);
            $sheet->setCellValue('J' . $rowIndex, $transaction->type_display);
            $sheet->setCellValue('K' . $rowIndex, $transaction->bank_transaction_id ?? '');
            $sheet->setCellValue('L' . $rowIndex, $transaction->paid_date ?? '');
            $sheet->setCellValue('M' . $rowIndex, $transaction->paid_time ?? '');
            $sheet->setCellValue('N' . $rowIndex, $transaction->payment_id);
            $sheet->setCellValue('O' . $rowIndex, $this->translateMonth($transaction->payment_month ?? ''));
            $sheet->setCellValue('P' . $rowIndex, $transaction->payment_year ?? '');
            $sheet->setCellValue('Q' . $rowIndex, $transaction->payment_total_amount ? number_format($transaction->payment_total_amount, 2, '.', ' ') : '');
            $sheet->setCellValue('R' . $rowIndex, $transaction->description ?? '');
            $sheet->setCellValue('S' . $rowIndex, $transaction->expires_date ?? '');
            $sheet->setCellValue('T' . $rowIndex, $transaction->qr_code_id ? ($transaction->expires_at && strtotime($transaction->expires_at) < time() ? 'Просрочен' : 'Активен') : '');

            $rowIndex++;
        }

        // Авторазмер колонок
        $columnCount = count($headers);
        for ($i = 1; $i <= $columnCount; $i++) {
            $columnLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        // Стили для заголовков
        $sheet->getStyle('A' . $headerRow . ':' . Coordinate::stringFromColumnIndex($columnCount) . $headerRow)
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF4F81BD');

        $sheet->getStyle('A' . $headerRow . ':' . Coordinate::stringFromColumnIndex($columnCount) . $headerRow)
            ->getFont()
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));

        // Границы для данных
        $dataStartRow = $headerRow + 1;
        $dataEndRow = $rowIndex - 1;

        if ($dataEndRow >= $dataStartRow) {
            $sheet->getStyle('A' . $dataStartRow . ':' . Coordinate::stringFromColumnIndex($columnCount) . $dataEndRow)
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }

        // Цвета для статусов
        $statusColumn = 'I';
        for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
            $status = $sheet->getCell($statusColumn . $row)->getValue();
            $color = $this->getStatusColor($status);

            if ($color) {
                $sheet->getStyle($statusColumn . $row)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB($color);
            }
        }

        // Создание файла
        $fileName = 'transactions_export_' . date('Y-m-d_His') . '.xlsx';
        $filePath = storage_path('app/exports/' . $fileName);

        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return [
            'path' => $filePath,
            'name' => $fileName,
            'count' => $data->count(),
            'total_amount' => $data->sum('amount'),
            'total_bonus_deduct' => $data->sum('bonus_deduct_amount')
        ];
    }

    private function translateTransactionStatus($status)
    {
        $translations = [
            'pending' => 'Ожидание',
            'processing' => 'В обработке',
            'completed' => 'Завершено',
            'failed' => 'Ошибка',
            'expired' => 'Просрочено',
            'cancelled' => 'Отменено'
        ];

        return $translations[$status] ?? $status;
    }

    private function translateTransactionType($type)
    {
        $translations = [
            'payment' => 'Платеж',
            'refund' => 'Возврат'
        ];

        return $translations[$type] ?? $type;
    }

    private function translateMonth($month)
    {
        $translations = [
            'january' => 'Январь',
            'february' => 'Февраль',
            'march' => 'Март',
            'april' => 'Апрель',
            'may' => 'Май',
            'june' => 'Июнь',
            'july' => 'Июль',
            'august' => 'Август',
            'september' => 'Сентябрь',
            'october' => 'Октябрь',
            'november' => 'Ноябрь',
            'december' => 'Декабрь'
        ];

        return $translations[strtolower($month)] ?? $month;
    }

    private function formatTransactionFilterName($filter)
    {
        $names = [
            'status' => 'Статус',
            'type' => 'Тип',
            'client' => 'Клиент',
            'date_from' => 'Дата от',
            'date_to' => 'Дата до',
            'paid_date_from' => 'Дата оплаты от',
            'paid_date_to' => 'Дата оплаты до',
            'amount_min' => 'Сумма от',
            'amount_max' => 'Сумма до',
            'bank_transaction_id' => 'ID банковской транзакции',
            'order_by' => 'Сортировка по',
            'order_dir' => 'Направление сортировки'
        ];

        return $names[$filter] ?? ucfirst(str_replace('_', ' ', $filter));
    }

    private function getStatusColor($status)
    {
        $colors = [
            'Ожидание' => 'FFFF00',     // Желтый
            'В обработке' => 'FFA500',  // Оранжевый
            'Завершено' => '00FF00',    // Зеленый
            'Ошибка' => 'FF0000',       // Красный
            'Просрочено' => 'A9A9A9',   // Серый
            'Отменено' => '808080'      // Темно-серый
        ];

        return $colors[$status] ?? null;
    }
}
