<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImportClientsFromExcel extends Command
{
    protected $signature = 'import:clients {file} {--format=csv}';
    protected $description = 'Import clients from Excel/CSV file';

    // Маппинг полей CSV на поля базы данных
    private $fieldMapping = [
        '\u{FEFF}№ договора' => 'contract_number',
        'ИД курьера' => 'courier_id',
        'Фамилия' => 'last_name',
        'Имя' => 'first_name',
        'Отчество' => 'middle_name',
        'Дата Рождения' => 'birth_date',
        'Телефон' => 'phone_number',
        'Доп телефон' => 'additional_phone',
        'Телефон знакомых' => 'relatives_phone',
        'Паспорт серия номер' => 'passport_full',
        'Кем выдан' => 'passport_issued_by',
        'Когда выдан' => 'passport_issue_date',
        'Код подразделения' => 'passport_department_code',
        'Адрес прописки' => 'legal_address',
        'Адрес проживания' => 'actual_address',
        'Дата оформления' => 'issue_date',
        'Курьерская служба' => 'courier_service',
        'Источник привлечения' => 'attraction_source',
        'Начало пользования' => 'service_start_date',
        'Конец пользования' => 'service_end_date',
        'Серийный номер' => '',
        '1й аккумулятор электровелосипеда' => '',
        '2й аккумулятор электровелосипеда' => ''
    ];

    // Поля для основной таблицы clients
    private $mainTableFields = [
        'phone_number', 'name'
    ];

    // Поля для custom_client_fields
    private $customFields = [
        'contract_number', 'courier_id', 'last_name', 'first_name', 'middle_name',
        'birth_date', 'phone_number', 'additional_phone', 'relatives_phone', 'passport_series',
        'passport_number', 'passport_issued_by', 'passport_issue_date',
        'passport_department_code', 'legal_address', 'actual_address', 'registration_date',
        'courier_service', 'attraction_source', 'service_start_date', 'service_end_date',
        'serial_number', 'battery_1', 'battery_2', 'issue_date'
    ];

    public function handle()
    {
        $file = $this->argument('file');
        $format = $this->option('format');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info("Starting import from: {$file}");

        try {
            $data = $this->readFile($file, $format);
            $this->processData($data);
        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            Log::error('Import failed: ' . $e->getMessage());
            return 1;
        }

        $this->info("Import completed successfully!");
        return 0;
    }

    private function readFile($file, $format)
    {
        $data = [];

        if ($format === 'csv') {
            $handle = fopen($file, 'r');
            if (!$handle) {
                throw new \Exception("Cannot open CSV file");
            }

            // Читаем заголовки
            $headers = fgetcsv($handle, null, ';');
            if (!$headers) {
                throw new \Exception("Cannot read CSV headers");
            }

            // Нормализуем заголовки
            $headers = array_map('trim', $headers);

            $rowNumber = 1;
            while (($row = fgetcsv($handle, null, ';')) !== FALSE) {
                $rowNumber++;

                if (count($row) !== count($headers)) {
                    Log::warning("Row {$rowNumber}: Column count mismatch, skipping");
                    continue;
                }

                $rowData = array_combine($headers, $row);
                $data[] = $rowData;
            }

            fclose($handle);
        } else {
            throw new \Exception("Unsupported format. Use CSV");
        }

        return $data;
    }

    private function processData($data)
    {
        $successCount = 0;
        $errorCount = 0;

        foreach ($data as $index => $row) {
            try {
                DB::transaction(function () use ($row, $index, &$successCount, &$errorCount) {
                    $this->processRow($row, $index + 2); // +2 потому что заголовки + 1-based индекс
                    $successCount++;
                });
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Row " . ($index + 2) . " import failed: " . $e->getMessage());
                $this->warn("Row " . ($index + 2) . " failed: " . $e->getMessage());
            }
        }

        $this->info("Import results: {$successCount} successful, {$errorCount} failed");
    }

    private function processRow($row, $rowNumber)
    {
        // Нормализуем данные
        $normalizedData = $this->normalizeData($row);

        // Валидация основных данных
        $this->validateMainData($normalizedData, $rowNumber);

        // Создаем запись в основной таблице
        $clientId = $this->createMainClientRecord($normalizedData, $rowNumber);

        // Создаем кастомные поля
        $this->createCustomFields($clientId, $normalizedData, $rowNumber);
    }

    private function normalizeData($row)
    {
        $normalized = [];

        foreach ($this->fieldMapping as $csvField => $dbField) {
            $value = $row[$csvField] ?? null;

            if ($value !== null) {
                $value = trim($value);

                // Обработка пустых значений
                if ($value === '' || $value === 'NULL' || $value === 'null') {
                    $value = null;
                }
            }

            $normalized[$dbField] = $value;
        }

        // Обработка паспортных данных
        if (!empty($normalized['passport_full'])) {
            $passportData = $this->parsePassport($normalized['passport_full']);
            $normalized['passport_series'] = $passportData['series'] ?? null;
            $normalized['passport_number'] = $passportData['number'] ?? null;
        }
        unset($normalized['passport_full']);

        // Обработка дат
        $dateFields = ['birth_date', 'passport_issue_date', 'registration_date',
            'service_start_date', 'service_end_date'];

        foreach ($dateFields as $field) {
            if (!empty($normalized[$field])) {
                $normalized[$field] = $this->parseDate($normalized[$field]);
            }
        }

        return $normalized;
    }

    private function parsePassport($passportData)
    {
        // Удаляем все пробелы и нецифровые символы (кроме цифр)
        $clean = preg_replace('/[^\d]/', '', $passportData);

        if (strlen($clean) === 10) {
            return [
                'series' => substr($clean, 0, 4),
                'number' => substr($clean, 4, 6)
            ];
        }

        return ['series' => null, 'number' => null];
    }

    private function parseDate($dateString)
    {
        try {
            // Пробуем разные форматы дат
            $formats = ['d.m.Y', 'd/m/Y', 'Y-m-d', 'd-m-Y'];

            foreach ($formats as $format) {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            }

            // Если не удалось распарсить, возвращаем как есть (валидатор потом отловит)
            return $dateString;
        } catch (\Exception $e) {
            return $dateString;
        }
    }

    private function validateMainData($data, $rowNumber)
    {
        // Проверяем обязательные поля
        if (empty($data['phone_number'])) {
            throw new \Exception("Phone number is required");
        }

        if (empty($data['first_name']) || empty($data['last_name'])) {
            throw new \Exception("First name and last name are required");
        }

        // Проверяем уникальность телефона
        $existingClient = DB::table('clients')
            ->where('phone_number', $data['phone_number'])
            ->first();

        if ($existingClient) {
            throw new \Exception("Phone number already exists: {$data['phone_number']}");
        }
    }

    private function createMainClientRecord($data, $rowNumber)
    {
        // Генерируем уникальные значения
        $telegramId = $this->generateUniqueTelegramId();
        $referralCode = $this->generateReferralCode();

        $clientData = [
            'telegram_id' => $telegramId,
            'phone_number' => $data['phone_number'],
            'name' => trim($data['last_name'] . ' ' . $data['first_name'] . ' ' . ($data['middle_name'] ?? '')),
            'registration_date' => now(),
            'referral_code' => $referralCode,
            'balance' => 0.00,
            'bonus_balance' => 0.00,
            'referred_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $clientId = DB::table('clients')->insertGetId($clientData);

        if (!$clientId) {
            throw new \Exception("Failed to create client record");
        }

        return $clientId;
    }

    private function createCustomFields($clientId, $data, $rowNumber)
    {
        foreach ($this->customFields as $field) {
            $value = $data[$field] ?? null;

            if ($value !== null) {
                // Определяем тип поля
                $fieldType = $this->getFieldType($field);

                // Валидация в зависимости от типа поля
                $this->validateCustomField($field, $value, $rowNumber);

                DB::table('custom_client_fields')->insert([
                    'client_id' => $clientId,
                    'field_name' => $field,
                    'field_type' => $fieldType,
                    'field_value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function getFieldType($fieldName)
    {
        $dateFields = ['birth_date', 'passport_issue_date', 'registration_date',
            'service_start_date', 'service_end_date', 'issue_date'];

        if (in_array($fieldName, $dateFields)) {
            return 'date';
        }

        return 'text';
    }

    private function validateCustomField($field, $value, $rowNumber)
    {
        $rules = [
            'passport_series' => ['nullable', 'string', 'size:4'],
            'passport_number' => ['nullable', 'string', 'size:6'],
            'passport_department_code' => ['nullable', 'string'],
            'phone_number' => ['required', 'string', 'max:20'],
            'additional_phone' => ['nullable', 'string', 'max:200'],
            'relatives_phone' => ['nullable', 'string', 'max:200'],
        ];

        if (isset($rules[$field])) {
            $validator = Validator::make([$field => $value], [$field => $rules[$field]]);

            if ($validator->fails()) {
                throw new \Exception("Field {$field} validation failed: " .
                    implode(', ', $validator->errors()->all()));
            }
        }

        // Проверка дат
        if (strpos($field, 'date') !== false && !empty($value)) {
            if (!strtotime($value)) {
                throw new \Exception("Field {$field} contains invalid date: {$value}");
            }
        }
    }

    private function generateUniqueTelegramId()
    {
        do {
            $telegramId = rand(100000000, 999999999);
            $exists = DB::table('clients')->where('telegram_id', $telegramId)->exists();
        } while ($exists);

        return $telegramId;
    }

    private function generateReferralCode()
    {
        do {
            $code = Str::upper(Str::random(8));
            $exists = DB::table('clients')->where('referral_code', $code)->exists();
        } while ($exists);

        return $code;
    }
}
