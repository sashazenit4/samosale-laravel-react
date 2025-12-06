<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Services\DocxRentalContractTemplateService;
use App\Services\PdfRentalContractTemplateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RentalContractController extends Controller
{
    protected $docxService;
    protected $pdfService;

    public function __construct(
        DocxRentalContractTemplateService $docxService,
        PdfRentalContractTemplateService $pdfService
    ) {
        $this->docxService = $docxService;
        $this->pdfService = $pdfService;
    }

    /**
     * Генерация договора аренды в DOCX
     */
    public function generateRentalContract($rentalId): BinaryFileResponse
    {
        // Получаем данные аренды с отношениями
        $rental = Rental::with([
            'client',
            'client.customFields'
        ])->findOrFail($rentalId);

        // Генерируем документ
        $filePath = $this->docxService->generateRentalContract(
            $rental,
            $rental->client,
            $rental->client->customFields
        );

        // Формируем имя файла для скачивания
        $clientName = $rental->client->customFields
            ->where('field_name', 'last_name')
            ->first()->field_value ?? 'client';

        $fileName = "Договор_аренды_{$clientName}_{$rentalId}.docx";

        // Возвращаем файл для скачивания
        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Генерация договора аренды в PDF
     */
    public function generateRentalContractPdf($rentalId): BinaryFileResponse
    {
        // Получаем данные аренды с отношениями
        $rental = Rental::with([
            'client',
            'client.customFields'
        ])->findOrFail($rentalId);

        // Генерируем PDF документ
        $filePath = $this->pdfService->generateRentalContract(
            $rental,
            $rental->client,
            $rental->client->customFields
        );

        $clientName = $rental->client->customFields
            ->where('field_name', 'last_name')
            ->first()->field_value ?? 'client';

        $fileName = "Договор_аренды_{$clientName}_{$rentalId}.pdf";

        // Возвращаем файл для скачивания
        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Получить информацию о аренде для генерации документа
     */
    public function getRentalData($rentalId): JsonResponse
    {
        $rental = Rental::with([
            'client',
            'client.customFields'
        ])->findOrFail($rentalId);

        // Подготавливаем данные для отображения
        $data = [
            'rental' => $rental->toArray(),
            'client' => $rental->client->toArray(),
            'custom_fields' => $rental->client->customFields->mapWithKeys(function ($field) {
                return [$field->field_name => $field->field_value];
            })->toArray(),
            'template_data' => $this->docxService->prepareReplacements(
                $rental,
                $rental->client,
                $rental->client->customFields
            )
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Генерация документа DOCX с возможностью переопределения данных через JSON
     */
    public function generateCustomContract(Request $request, $rentalId): BinaryFileResponse
    {
        $rental = Rental::with(['client', 'client.customFields'])->findOrFail($rentalId);

        // Если переданы кастомные данные, используем их
        if ($request->has('custom_data')) {
            $customFields = collect($request->input('custom_data'))
                ->map(function ($value, $key) {
                    return (object)[
                        'field_name' => $key,
                        'field_value' => $value
                    ];
                });

            $filePath = $this->docxService->generateRentalContract(
                $rental,
                $rental->client,
                $customFields
            );
        } else {
            $filePath = $this->docxService->generateRentalContract(
                $rental,
                $rental->client,
                $rental->client->customFields
            );
        }

        $fileName = "Договор_аренды_{$rentalId}_" . time() . ".docx";

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Генерация документа PDF с возможностью переопределения данных через JSON
     */
    public function generateCustomContractPdf(Request $request, $rentalId): BinaryFileResponse
    {
        $rental = Rental::with(['client', 'client.customFields'])->findOrFail($rentalId);

        // Если переданы кастомные данные, используем их
        if ($request->has('custom_data')) {
            $customFields = collect($request->input('custom_data'))
                ->map(function ($value, $key) {
                    return (object)[
                        'field_name' => $key,
                        'field_value' => $value
                    ];
                });

            $filePath = $this->pdfService->generateRentalContract(
                $rental,
                $rental->client,
                $customFields
            );
        } else {
            $filePath = $this->pdfService->generateRentalContract(
                $rental,
                $rental->client,
                $rental->client->customFields
            );
        }

        $fileName = "Договор_аренды_{$rentalId}_" . time() . ".pdf";

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Проверка доступности шаблона DOCX
     */
    public function checkTemplate(): JsonResponse
    {
        $templatePath = resource_path('templates/rental_contract_template.docx');
        $exists = file_exists($templatePath);

        return response()->json([
            'success' => $exists,
            'template_exists' => $exists,
            'template_path' => $templatePath,
            'message' => $exists ? 'Шаблон DOCX доступен' : 'Шаблон DOCX не найден'
        ]);
    }

    /**
     * Проверка доступности шаблона PDF
     */
    public function checkPdfTemplate(): JsonResponse
    {
        $templatePath = resource_path('templates/rental_contract_template.html');
        $exists = file_exists($templatePath);

        return response()->json([
            'success' => $exists,
            'template_exists' => $exists,
            'template_path' => $templatePath,
            'message' => $exists ? 'Шаблон PDF доступен' : 'Шаблон PDF не найден'
        ]);
    }
}
