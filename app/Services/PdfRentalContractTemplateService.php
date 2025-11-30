<?php

namespace App\Services;

use App\Models\Bike;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class PdfRentalContractTemplateService
{
    public function generateRentalContract($rental, $client, $customFields)
    {
        // Сначала генерируем DOCX файл используя существующий сервис
        $docxService = app(DocxRentalContractTemplateService::class);
        $docxPath = $docxService->generateRentalContract($rental, $client, $customFields);

        try {
            // Конвертируем DOCX в PDF
            $pdfPath = $this->convertDocxToPdf($docxPath);

            // Удаляем временный DOCX файл
            if (file_exists($docxPath)) {
                unlink($docxPath);
            }

            return $pdfPath;

        } catch (\Exception $e) {
            // Если конвертация не удалась, удаляем временные файлы и пробрасываем исключение
            if (file_exists($docxPath)) {
                unlink($docxPath);
            }
            throw new \Exception("Ошибка конвертации DOCX в PDF: " . $e->getMessage());
        }
    }

    private function convertDocxToPdf($docxPath)
    {
        // Вариант 1: Используем PHPWord + DomPDF (более сложный, но бесплатный)
        return $this->convertDocxToPdfWithPhpWord($docxPath);

        // Вариант 2: Используем LibreOffice (требует установки LibreOffice на сервере)
        // return $this->convertDocxToPdfWithLibreOffice($docxPath);

        // Вариант 3: Используем облачный API (платный, но надежный)
        // return $this->convertDocxToPdfWithApi($docxPath);
    }

    private function convertDocxToPdfWithPhpWord($docxPath)
    {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($docxPath);
            $pdfWriter = new \PhpOffice\PhpWord\Writer\PDF\MPDF($phpWord);

            $fileName = 'contract_rental_' . time() . '.pdf';
            $pdfPath = storage_path('app/temp/' . $fileName);

            // Создаем директорию если не существует
            if (!file_exists(dirname($pdfPath))) {
                mkdir(dirname($pdfPath), 0755, true);
            }

            $pdfWriter->save($pdfPath);

            return $pdfPath;

        } catch (\Exception $e) {
            throw $e;
        }
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
            if ($rental->batteries_count > 1) {
                $batteryInfo .= ' (количество - ' . $rental->batteries_count . ' шт.)';
            }
        }

        $bikeInfo = Bike::where('id', $rental->bike_id)->first();

        return [
            'contract_number' => $customFieldsArray['contract_number'] ?? 'БН',
            'registration_date' => $registrationDate,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'passport_series_number' => $passportSeriesNumber,
            'passport_issued_by' => $customFieldsArray['passport_issued_by'] ?? '',
            'passport_issue_date' => isset($customFieldsArray['passport_issue_date'])
                ? date('d.m.Y', strtotime($customFieldsArray['passport_issue_date']))
                : '',
            'passport_department_code' => $customFieldsArray['passport_department_code'] ?? '',
            'legal_address' => $customFieldsArray['legal_address'] ?? '',
            'initials_i' => $i,
            'initials_o' => $o,
            'frame_number' => $bikeInfo->frame_number ?? '',
            'battery_info' => $batteryInfo,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rental' => $rental,
            'client' => $client,
            'custom_fields' => $customFieldsArray
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
        return View::exists('templates.rental_contract');
    }
}
