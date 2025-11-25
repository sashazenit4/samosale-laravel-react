<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Services\DocxRentalContractTemplateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RentalContractController extends Controller
{
    protected $docxService;

    public function __construct(DocxRentalContractTemplateService $docxService)
    {
        $this->docxService = $docxService;
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
     * Генерация документа с возможностью переопределения данных через JSON
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
     * Проверка доступности шаблона
     */
    public function checkTemplate(): JsonResponse
    {
        $templatePath = resource_path('templates/rental_contract_template.docx');
        $exists = file_exists($templatePath);

        return response()->json([
            'success' => $exists,
            'template_exists' => $exists,
            'template_path' => $templatePath,
            'message' => $exists ? 'Шаблон доступен' : 'Шаблон не найден'
        ]);
    }
}
