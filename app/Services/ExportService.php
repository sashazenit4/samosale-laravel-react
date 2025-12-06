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
}
