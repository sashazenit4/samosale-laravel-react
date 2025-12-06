<?php

namespace App\Services;

use App\Models\Bike;
use PhpOffice\PhpWord\TemplateProcessor;

class DocxRentalContractTemplateService
{
    public function generateRentalContract($rental, $client, $customFields, $isPdf = false)
    {
        if ($isPdf) {
            $templatePath = resource_path('templates/rental_contract_template.docx');
        } else {
            // Загружаем шаблон
            $templatePath = resource_path('templates/rental_contract_template_no_sign.docx');
        }

        if (!file_exists($templatePath)) {
            throw new \Exception("Шаблон документа не найден по пути: {$templatePath}");
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        // Подготавливаем данные для замены
        $replacements = $this->prepareReplacements($rental, $client, $customFields);

        // Заменяем плейсхолдеры в шаблоне
        foreach ($replacements as $key => $value) {
            try {
                $templateProcessor->setValue($key, $value ?? '');
            } catch (\Exception $e) {
                // Логируем ошибку, но продолжаем выполнение
                \Log::warning("Не удалось заменить плейсхолдер: {$key}", ['error' => $e->getMessage()]);
            }
        }

        // Сохраняем временный файл
        $fileName = 'contract_rental_' . $rental->id . '_' . time() . '.docx';
        $tempPath = storage_path('app/temp/' . $fileName);

        // Создаем директорию если не существует
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $templateProcessor->saveAs($tempPath);

        return $tempPath;
    }

    public function prepareReplacements($rental, $client, $customFields)
    {
        // Преобразуем customFields в ассоциативный массив для удобства
        $customFieldsArray = [];
        foreach ($customFields as $field) {
            $customFieldsArray[$field->field_name] = $field->field_value;
        }

        // Получаем данные клиента
        $lastName = $customFieldsArray['last_name'] ?? '';
        $firstName = $customFieldsArray['first_name'] ?? '';
        $middleName = $customFieldsArray['middle_name'] ?? '';

        // Генерируем инициалы
        $i = $firstName ? mb_substr($firstName, 0, 1, 'UTF-8') : '';
        $o = $middleName ? mb_substr($middleName, 0, 1, 'UTF-8') : '';

        // Обработка паспортных данных
        $passportSeries = $customFieldsArray['passport_series'] ?? '';
        $passportNumber = $customFieldsArray['passport_number'] ?? '';
        $passportSeriesNumber = trim($passportSeries . ' ' . $passportNumber);

        // Обработка дат
        $registrationDate = isset($customFieldsArray['registration_date'])
            ? date('d.m.Y', strtotime($customFieldsArray['registration_date']))
            : date('d.m.Y');

        $startDate = $rental->start_date
            ? (is_string($rental->start_date) ? date('d.m.Y', strtotime($rental->start_date)) : $rental->start_date->format('d.m.Y'))
            : '';

        $endDate = $rental->planned_end_date
            ? (is_string($rental->planned_end_date) ? date('d.m.Y', strtotime($rental->planned_end_date)) : $rental->planned_end_date->format('d.m.Y'))
            : '';

        // Обработка аккумуляторов
        $batteryInfo = '';
        if ($rental->battery_capacity) {
            $batteryInfo = $rental->battery_capacity . ' Ah';
            $batteryInfo .= ' (количество - ' . $rental->batteries_count . ' шт.)';
        }

        $bikeInfo = Bike::where('id', $rental->bike_id)->first();

        return [
            '№ договора' => $customFieldsArray['contract_number'] ?? 'БН',
            'Дата оформления' => $registrationDate,
            'Фамилия' => $lastName,
            'Имя' => $firstName,
            'Отчество' => $middleName,
            'Паспорт серия номер' => $passportSeriesNumber,
            'Кем выдан' => $customFieldsArray['passport_issued_by'] ?? '',
            'Когда выдан' => isset($customFieldsArray['passport_issue_date'])
                ? date('d.m.Y', strtotime($customFieldsArray['passport_issue_date']))
                : '',
            'Код подразделения' => $customFieldsArray['passport_department_code'] ?? '',
            'Адрес прописки' => $customFieldsArray['legal_address'] ?? '',
            'I' => $i,
            'O' => $o,
            'Серийный номер' => $bikeInfo->frame_number ?? '',
            'Аккумулятор электровелосипеда' => $batteryInfo,
            'Начало пользования' => $startDate,
            'Конец пользования' => $endDate,
        ];
    }

    /**
     * Очистка временных файлов
     */
    public function cleanupTempFile($filePath)
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Проверка существования шаблона
     */
    public function checkTemplateExists(): bool
    {
        $templatePath = resource_path('templates/rental_contract_template.docx');
        return file_exists($templatePath);
    }
}
