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

    private $relationsMap = [
        'bonus_operations' => [
            'client_id' => ['table' => 'clients', 'display' => 'name'],
            'transaction_id' => ['table' => 'transactions', 'display' => 'id'],
        ],
        'payments' => [
            'client_id' => ['table' => 'clients', 'display' => 'name'],
            'rental_id' => ['table' => 'rentals', 'display' => 'id'],
        ],
        'rentals' => [
            'client_id' => ['table' => 'clients', 'display' => 'name'],
            'bike_id' => ['table' => 'bikes', 'display' => 'bike_number'],
            'tariff_id' => ['table' => 'tariffs', 'display' => 'program'],
        ],
        'transactions' => [
            'payment_id' => ['table' => 'payments', 'display' => 'id'],
            'client_id' => ['table' => 'clients', 'display' => 'name'],
        ],
        'custom_client_fields' => [
            'client_id' => ['table' => 'clients', 'display' => 'name'],
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
        $query = DB::table($tableName);

        // Apply filters if any
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, 'like', "%{$value}%");
            }
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
        foreach ($this->relationsMap[$tableName] as $foreignKey => $relation) {
            $relatedTable = $relation['table'];
            $displayField = $relation['display'];

            // Get all foreign keys from data
            $foreignKeys = $data->pluck($foreignKey)->filter()->unique();

            if ($foreignKeys->isNotEmpty()) {
                // Fetch related data
                $relatedData = DB::table($relatedTable)
                    ->whereIn('id', $foreignKeys)
                    ->get()
                    ->keyBy('id');

                // Add relation to each item
                foreach ($data as $item) {
                    if ($item->$foreignKey && isset($relatedData[$item->$foreignKey])) {
                        $item->{$foreignKey . '_display'} = $relatedData[$item->$foreignKey]->$displayField;
                    } else {
                        $item->{$foreignKey . '_display'} = null;
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

        // Get all client IDs
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
            $sheet->setCellValue($columnLetter . '1', $this->formatHeader($header));
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
                if (in_array($header, ['created_at', 'updated_at', 'start_date', 'end_date', 'paid_at'])) {
                    $value = $this->formatDate($value);
                }

                $sheet->setCellValue($columnLetter . $rowIndex, $value);
            }
            $rowIndex++;
        }

        // Auto size columns - исправленная версия
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
        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex($columnCount) . ($rowIndex - 1))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

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

    private function formatHeader(string $header): string
    {
        // Format header names
        $header = str_replace('_', ' ', $header);
        $header = str_replace('_display', ' (related)', $header);

        // Special formatting for common fields
        $replacements = [
            'id' => 'ID',
            'created at' => 'Дата создания',
            'updated at' => 'Дата обновления',
            'client id' => 'ID клиента',
            'client id display' => 'Клиент',
            'bike id' => 'ID велосипеда',
            'bike id display' => 'Номер велосипеда',
            'tariff id' => 'ID тарифа',
            'tariff id display' => 'Тариф',
            'transaction id' => 'ID транзакции',
            'payment id' => 'ID платежа',
            'rental id' => 'ID аренды',
            'user id' => 'ID пользователя',
            'phone number' => 'Номер телефона',
            'registration date' => 'Дата регистрации',
            'referral code' => 'Реферальный код',
            'referred by' => 'Приглашен пользователем',
            'bonus balance' => 'Бонусный баланс',
        ];

        $lowerHeader = strtolower($header);
        if (isset($replacements[$lowerHeader])) {
            return $replacements[$lowerHeader];
        }

        return ucwords($header);
    }

    private function formatDate($value)
    {
        if (!$value) {
            return '';
        }

        try {
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d H:i:s');
            }

            if (is_string($value)) {
                $timestamp = strtotime($value);
                if ($timestamp !== false) {
                    return date('Y-m-d H:i:s', $timestamp);
                }
            }

            return $value;
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function getAvailableTables(): array
    {
        return array_keys($this->tableModels);
    }
}
