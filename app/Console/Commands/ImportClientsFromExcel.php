<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\ClientRepository;
use Carbon\Carbon;

class ImportClientsFromExcel extends Command
{
    protected $signature = 'import:clients
                            {file : Path to the CSV file}
                            {--format=csv : File format (csv only)}
                            {--skip-existing : Skip rows with existing phone numbers}
                            {--batch-size=100 : Number of rows to process in a batch}';

    protected $description = 'Import clients from Excel/CSV file using ClientRepository';

    // Маппинг полей CSV на поля базы данных
    private $fieldMapping = [
        '№ договора' => 'contract_number',
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
        'Дата оформления' => 'registration_date',
        'Курьерская служба' => 'courier_service',
        'Источник привлечения' => 'attraction_source',
        'Начало пользования' => 'service_start_date',
        'Конец пользования' => 'service_end_date',
        'Серийный номер' => 'serial_number',
        '1й аккумулятор электровелосипеда' => 'battery_1',
        '2й аккумулятор электровелосипеда' => 'battery_2',
        'telegram_id' => 'telegram_id',
    ];

    private $clientRepository;

    public function __construct(ClientRepository $clientRepository)
    {
        parent::__construct();
        $this->clientRepository = $clientRepository;
    }

    public function handle()
    {
        $file = $this->argument('file');
        $format = $this->option('format');
        $skipExisting = $this->option('skip-existing');
        $batchSize = (int) $this->option('batch-size');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info("Starting import from: {$file}");
        $this->info("Skip existing: " . ($skipExisting ? 'Yes' : 'No'));
        $this->info("Batch size: {$batchSize}");

        try {
            $results = $this->processFile($file, $format, $skipExisting, $batchSize);

            $this->info("\nImport completed!");
            $this->info("Successfully imported: {$results['success']}");
            $this->info("Skipped (existing): {$results['skipped']}");
            $this->info("Failed: {$results['failed']}");

            if ($results['failed'] > 0) {
                $this->warn("Check the log file for details on failed rows.");
            }

        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            Log::error('Import failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function processFile($file, $format, $skipExisting, $batchSize)
    {
        $results = [
            'success' => 0,
            'skipped' => 0,
            'failed' => 0
        ];

        $handle = fopen($file, 'r');
        if (!$handle) {
            throw new \Exception("Cannot open file");
        }

        // Читаем заголовки
        $headers = fgetcsv($handle, null,';');
        if (!$headers) {
            fclose($handle);
            throw new \Exception("Cannot read CSV headers");
        }

        $headers = array_map('trim', $headers);
        $batch = [];
        $rowNumber = 1;

        $this->output->progressStart();

        while (($row = fgetcsv($handle, null, ';')) !== FALSE) {
            $rowNumber++;

            if (count($row) !== count($headers)) {
                Log::warning("Row {$rowNumber}: Column count mismatch, skipping");
                $results['failed']++;
                continue;
            }

            $rowData = array_combine($headers, $row);
            $normalizedData = $this->normalizeRowData($rowData);

            // Проверяем существующий номер телефона
            if ($skipExisting && $this->phoneNumberExists($normalizedData['phone_number'])) {
                $results['skipped']++;
                Log::info("Row {$rowNumber}: Skipped - phone number already exists");
                continue;
            }

            $batch[] = [
                'data' => $normalizedData,
                'row_number' => $rowNumber
            ];

            // Обрабатываем батч
            if (count($batch) >= $batchSize) {
                $batchResults = $this->processBatch($batch);
                $results['success'] += $batchResults['success'];
                $results['failed'] += $batchResults['failed'];
                $batch = [];
            }

            $this->output->progressAdvance();
        }

        // Обрабатываем оставшиеся записи
        if (!empty($batch)) {
            $batchResults = $this->processBatch($batch);
            $results['success'] += $batchResults['success'];
            $results['failed'] += $batchResults['failed'];
        }

        fclose($handle);
        $this->output->progressFinish();

        return $results;
    }

    private function processBatch($batch)
    {
        $results = [
            'success' => 0,
            'failed' => 0
        ];

        foreach ($batch as $item) {
            try {
                DB::transaction(function () use ($item, &$results) {
                    $this->createClientFromRow($item['data'], $item['row_number']);
                    $results['success']++;
                });
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error("Row {$item['row_number']} import failed: " . $e->getMessage());
            }
        }

        return $results;
    }

    private function normalizeRowData($rowData)
    {
        $normalized = [];

        foreach ($this->fieldMapping as $csvField => $dbField) {
            $value = $rowData[$csvField] ?? null;

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

        // Нормализация телефона
        if (!empty($normalized['phone_number'])) {
            $normalized['phone_number'] = $this->normalizePhone($normalized['phone_number']);
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

        // Пробуем другие форматы
        if (preg_match('/(\d{4})\s*(\d{6})/', $passportData, $matches)) {
            return [
                'series' => $matches[1],
                'number' => $matches[2]
            ];
        }

        return ['series' => null, 'number' => null];
    }

    private function parseDate($dateString)
    {
        try {
            // Пробуем разные форматы дат
            $formats = ['d.m.Y', 'd/m/Y', 'Y-m-d', 'd-m-Y', 'Y.m.d'];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $dateString);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            }

            // Если дата в формате Excel (серийный номер)
            if (is_numeric($dateString)) {
                $unixTimestamp = ($dateString - 25569) * 86400; // Convert Excel date to Unix timestamp
                return date('Y-m-d', $unixTimestamp);
            }

            return $dateString;
        } catch (\Exception $e) {
            return $dateString;
        }
    }

    private function normalizePhone($phone)
    {
        // Удаляем все нецифровые символы
        $clean = preg_replace('/[^\d]/', '', $phone);

        // Если номер начинается с 8, заменяем на 7
        if (strlen($clean) === 11 && $clean[0] === '8') {
            $clean = '7' . substr($clean, 1);
        }

        // Если номер начинается без кода страны, добавляем 7
        if (strlen($clean) === 10) {
            $clean = '7' . $clean;
        }

        return $clean;
    }

    private function phoneNumberExists($phone)
    {
        if (empty($phone)) {
            return false;
        }

        return $this->clientRepository->phoneNumberExists($phone);
    }

    private function createClientFromRow($data, $rowNumber)
    {
        // Проверяем обязательные поля
        if (empty($data['phone_number'])) {
            throw new \Exception("Phone number is required");
        }

        if (empty($data['first_name']) || empty($data['last_name'])) {
            throw new \Exception("First name and last name are required");
        }

        // Генерируем telegram_id (используем временный, если не нужен)
        // В репозитории будет использоваться логика приглашений, если telegram_id не указан
        $telegramId = $data['telegram_id'];

        // Подготавливаем данные для создания клиента
        $clientData = [
            'telegram_id' => $telegramId,
            'phone_number' => $data['phone_number'],
            'name' => trim($data['last_name'] . ' ' . $data['first_name'] . ' ' . ($data['middle_name'] ?? '')),
        ];

        // Подготавливаем кастомные поля
        $customFields = $this->prepareCustomFields($data);

        // Добавляем кастомные поля в данные клиента
        $clientData['custom_fields'] = $customFields;

        // Создаем клиента через репозиторий
        $this->clientRepository->create($clientData);
    }

    private function prepareCustomFields($data)
    {
        $customFields = [];

        // Определяем тип поля на основе имени
        $getFieldType = function ($fieldName) {
            $dateFields = ['birth_date', 'passport_issue_date', 'registration_date',
                'service_start_date', 'service_end_date', 'issue_date'];

            if (in_array($fieldName, $dateFields)) {
                return 'date';
            }

            return 'text';
        };

        // Все поля, кроме phone_number, name, telegram_id идем в кастомные
        $excludeFromCustom = ['phone_number', 'name', 'telegram_id'];

        foreach ($data as $fieldName => $value) {
            if (in_array($fieldName, $excludeFromCustom)) {
                continue;
            }

            if ($value !== null) {
                $customFields[] = [
                    'name' => $fieldName,
                    'value' => (string) $value,
                    'type' => $getFieldType($fieldName)
                ];
            }
        }

        return $customFields;
    }
}
