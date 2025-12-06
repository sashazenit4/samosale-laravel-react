<?php

namespace App\Services;

use App\Models\Bike;
use Exception;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class PdfRentalContractTemplateService
{
    public function generateRentalContract($rental, $client, $customFields): string
    {
        // 1. Генерируем DOCX через твой существующий сервис
        $docxService = app(DocxRentalContractTemplateService::class);
        $docxPath = $docxService->generateRentalContract($rental, $client, $customFields);

        try {
            // 2. Конвертируем в PDF через LibreOffice (максимальная точность)
            $pdfPath = $this->convertDocxToPdfWithLibreOffice($docxPath);

            // 3. Удаляем временный DOCX
            $this->cleanupTempFile($docxPath);

            return $pdfPath;

        } catch (Exception $e) {
            // На всякий случай чистим файлы при ошибке
            $this->cleanupTempFile($docxPath);
            throw new Exception('Ошибка конвертации договора в PDF: ' . $e->getMessage());
        }
    }

    /**
     * Конвертация через LibreOffice — золотой стандарт точности
     */
    private function convertDocxToPdfWithLibreOffice(string $docxPath): string
    {
        if (!file_exists($docxPath)) {
            throw new Exception('DOCX файл не найден: ' . $docxPath);
        }

        $inputDir  = dirname($docxPath);
        $fileName  = pathinfo($docxPath, PATHINFO_FILENAME);
        $random    = Str::random(10);
        $pdfName   = "{$fileName}_{$random}.pdf";
        $pdfPath   = storage_path("app/contracts/pdf/{$pdfName}");

        // Создаём папку, если нет
        if (!is_dir(dirname($pdfPath))) {
            mkdir(dirname($pdfPath), 0755, true);
        }

        // Команда для Linux/macOS
        $command = [
            'libreoffice',
            '--headless',
            '--convert-to', 'pdf:writer_pdf_Export',
            '--outdir', dirname($pdfPath),
            $docxPath
        ];

        $process = new Process($command);
        $process->setTimeout(15); // 2 минуты на конвертацию
        $process->run();

        // Переименовываем файл, потому что LibreOffice сохраняет с оригинальным именем
        $generatedPdf = dirname($pdfPath) . '/' . $fileName . '.pdf';

        if (!$process->isSuccessful() || !file_exists($generatedPdf)) {
            \Log::error('LibreOffice conversion failed', [
                'command' => $command,
                'output'  => $process->getOutput(),
                'error'   => $process->getErrorOutput(),
            ]);
            throw new Exception('LibreOffice не смог конвертировать файл. Смотри логи.');
        }

        // Перемещаем в нужное место с уникальным именем
        rename($generatedPdf, $pdfPath);

        return $pdfPath;
    }

    /**
     * Удаление временного файла
     */
    public function cleanupTempFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            @unlink($filePath);
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
}
