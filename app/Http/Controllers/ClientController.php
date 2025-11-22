<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Repositories\ClientRepository;
use App\Http\Resources\ClientResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    protected ClientRepository $clientRepository;

    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * Display a listing of clients.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $clients = $this->clientRepository->getAllPaginated($perPage);

        return response()->json([
            'success' => true,
            'data' => $clients->items(),
            'pagination' => [
                'current_page' => $clients->currentPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
                'last_page' => $clients->lastPage(),
            ]
        ]);
    }

    /**
     * Store a newly created client.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'telegram_id' => 'required|integer|unique:clients,telegram_id',
            'phone_number' => 'required|string|max:32|unique:clients,phone_number',
            'name' => 'nullable|string|max:255',
            'referral_code' => 'sometimes|string|max:32|unique:clients,referral_code',
            'referred_by' => 'nullable|integer|exists:clients,user_id',
            'custom_fields' => 'nullable|array',
            'custom_fields.*.name' => 'required|string|max:255',
            'custom_fields.*.value' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $client = $this->clientRepository->create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Client created successfully',
                'data' => $client
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for custom fields',
                'errors' => json_decode($e->getMessage(), true)
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified client.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $client = $this->clientRepository->findById($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'phone_number' => 'sometimes|string|max:32|unique:clients,phone_number,' . $id . ',user_id',
            'name' => 'sometimes|string|max:255',
            'referral_code' => 'sometimes|string|max:32|unique:clients,referral_code,' . $id . ',user_id',
            'referred_by' => 'nullable|integer|exists:clients,user_id',
            'custom_fields' => 'nullable|array',
            'custom_fields.*.name' => 'required|string|max:255',
            'custom_fields.*.value' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $this->clientRepository->update($id, $validator->validated());
            $updatedClient = $this->clientRepository->findById($id);

            return response()->json([
                'success' => true,
                'message' => 'Client updated successfully',
                'data' => $updatedClient
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for custom fields',
                'errors' => json_decode($e->getMessage(), true)
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get allowed custom field templates
     */
    public function getFieldTemplates(): JsonResponse
    {
        try {
            $templates = $this->clientRepository->getAllowedFieldTemplates();

            return response()->json([
                'success' => true,
                'data' => $templates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch field templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate custom field value
     */
    public function validateField(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'field_name' => 'required|string|max:255',
            'field_value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->clientRepository->validateFieldValue(
                $request->field_name,
                $request->field_value
            );

            return response()->json([
                'success' => $result['valid'],
                'data' => $result
            ], $result['valid'] ? 200 : 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate field',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified client.
     */
    public function show(int $id): JsonResponse
    {
        $client = $this->clientRepository->findById($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $client->load('referrer', 'referrals')
        ]);
    }

    /**
     * Display client by Telegram ID.
     */
    public function showByTelegramId(int $telegramId): JsonResponse
    {
        $client = $this->clientRepository->findByTelegramId($telegramId);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $client->load('referrer', 'referrals')
        ]);
    }

    /**
     * Remove the specified client.
     */
    public function destroy(int $id): JsonResponse
    {
        $client = $this->clientRepository->findById($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found'
            ], 404);
        }

        try {
            $this->clientRepository->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Client deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get client custom fields.
     */
    public function getCustomFields(int $id): JsonResponse
    {
        $client = $this->clientRepository->findById($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $client->customFields
        ]);
    }

    /**
     * Update specific custom field for client.
     */
    public function updateCustomField(Request $request, int $id): JsonResponse
    {
        $client = $this->clientRepository->findById($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'field_name' => 'required|string|max:255',
            'field_value' => 'required|string',
            'field_type' => 'nullable|string|in:text,number,date,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $client->setCustomField(
                $data['field_name'],
                $data['field_value'],
                $data['field_type'] ?? 'text'
            );

            return response()->json([
                'success' => true,
                'message' => 'Custom field updated successfully',
                'data' => $client->load('customFields')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update custom field',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Остальные существующие методы остаются без изменений
    public function statistics(int $id): JsonResponse
    {
        $statistics = $this->clientRepository->getStatistics($id);

        if (empty($statistics)) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    public function referrals(int $id): JsonResponse
    {
        $client = $this->clientRepository->findById($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found'
            ], 404);
        }

        $referrals = $this->clientRepository->getReferrals($id);

        return response()->json([
            'success' => true,
            'data' => $referrals
        ]);
    }

    public function checkTelegramId(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'telegram_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $exists = $this->clientRepository->telegramIdExists($request->telegram_id);

        return response()->json([
            'success' => true,
            'exists' => $exists
        ]);
    }

    public function checkPhoneNumber(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:32'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $exists = $this->clientRepository->phoneNumberExists($request->phone_number);

        return response()->json([
            'success' => true,
            'exists' => $exists
        ]);
    }

    /**
     * Проверить наличие активной аренды у клиента
     */
    public function checkActiveRental(Client $client): JsonResponse
    {
        try {
            $activeRental = $client->activeRentals()->first();

            return response()->json([
                'success' => true,
                'has_active_rental' => $activeRental !== null,
                'rental_id'         => $activeRental?->id,
                'client_id'         => $client->user_id,
                'client_name'       => $client->name,
                'telegram_id'       => $client->telegram_id,
                'planned_end_date'  => $activeRental?->planned_end_date,
                'start_date' => $activeRental?->start_date,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check active rental',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Проверить наличие активной аренды по telegram_id
     */
    public function checkActiveRentalByTelegram($telegramId): JsonResponse
    {
        try {
            $client = Client::where('telegram_id', $telegramId)->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            $activeRental = $client->activeRentals()->first();
            $clientData = new ClientResource($client);


            return response()->json([
                'success' => true,
                'has_active_rental' => $activeRental !== null,
                'rental_id'         => $activeRental?->id,
                'client_id'         => $client->user_id,
                'client_name'       => $client->name,
                'telegram_id'       => $client->telegram_id,
                'planned_end_date'  => $activeRental?->planned_end_date,
                'start_date' => $activeRental?->start_date,
                'client' => $clientData,
                'bike' => $activeRental?->bike,
                'tariff' => $activeRental?->tariff,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check active rental',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
