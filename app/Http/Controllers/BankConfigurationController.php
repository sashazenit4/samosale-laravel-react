<?php
namespace App\Http\Controllers;

use App\Models\BankConfiguration;
use App\Services\TochkaBankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankConfigurationController extends Controller
{
    /**
     * Получить список банковских конфигураций
     */
    public function index(): JsonResponse
    {
        $configs = BankConfiguration::all();

        return response()->json([
            'data' => $configs->map(function ($config) {
                return [
                    'id' => $config->id,
                    'name' => $config->name,
                    'environment' => $config->environment,
                    'legal_id' => $config->legal_id,
                    'merchant_id' => $config->merchant_id,
                    'account_id' => $config->account_id,
                    'customer_code' => $config->customer_code,
                    'is_active' => $config->is_active,
                    'is_complete' => $config->isComplete(),
                    'created_at' => $config->created_at,
                    'updated_at' => $config->updated_at,
                ];
            })
        ]);
    }

    /**
     * Обновить банковскую конфигурацию
     */
    public function update(Request $request, BankConfiguration $bankConfiguration): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'environment' => 'sometimes|in:sandbox,production',
            'legal_id' => 'sometimes|string|max:255',
            'merchant_id' => 'sometimes|string|max:255',
            'account_id' => 'sometimes|string|max:255',
            'jwt_token' => 'sometimes|string',
            'customer_code' => 'sometimes|string|max:255',
            'bank_code' => 'sometimes|string|max:255',
            'brand_name' => 'sometimes|string|max:255',
            'mcc' => 'sometimes|string|max:10',
            'contact_phone' => 'sometimes|string|max:20',
            'city' => 'sometimes|string|max:100',
            'country_code' => 'sometimes|string|max:2',
            'is_active' => 'sometimes|boolean',
        ]);

        $bankConfiguration->update($validated);

        return response()->json([
            'message' => 'Конфигурация обновлена',
            'data' => $bankConfiguration,
            'is_complete' => $bankConfiguration->isComplete()
        ]);
    }

    /**
     * Проверить соединение с банком
     */
    public function testConnection(BankConfiguration $bankConfiguration): JsonResponse
    {
        try {
            $bankService = new TochkaBankService($bankConfiguration->environment);

            // Проверяем информацию о мерчанте
            $merchantResult = $bankService->getMerchantInfo();

            // Проверяем информацию о юридическом лице
            $legalEntityResult = $bankService->getLegalEntityInfo();

            if ($merchantResult['success'] && $legalEntityResult['success']) {
                return response()->json([
                    'message' => 'Соединение с банком установлено успешно',
                    'merchant_info' => $merchantResult['merchant'],
                    'legal_entity_info' => $legalEntityResult['legal_entity'],
                    'is_active' => $bankConfiguration->is_active,
                    'is_complete' => $bankConfiguration->isComplete()
                ]);
            } else {
                $errors = [];
                if (!$merchantResult['success']) {
                    $errors[] = "Merchant: {$merchantResult['error']}";
                }
                if (!$legalEntityResult['success']) {
                    $errors[] = "Legal Entity: {$legalEntityResult['error']}";
                }

                return response()->json([
                    'message' => 'Ошибка соединения с банком',
                    'errors' => $errors
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка при проверке соединения',
                'error' => $e->getMessage(),
                'is_complete' => $bankConfiguration->isComplete()
            ], 500);
        }
    }

    /**
     * Получить список счетов
     */
    public function getAccounts(BankConfiguration $bankConfiguration): JsonResponse
    {
        try {
            $bankService = new TochkaBankService($bankConfiguration->environment);
            $result = $bankService->getAccountsList();

            if ($result['success']) {
                return response()->json([
                    'message' => 'Список счетов получен',
                    'accounts' => $result['accounts']
                ]);
            } else {
                return response()->json([
                    'message' => 'Ошибка при получении списка счетов',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка при получении списка счетов',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
