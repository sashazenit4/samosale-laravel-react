<?php

namespace App\Http\Controllers;

use App\Repositories\LoyaltyProgramKeyRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function __construct(
        private readonly LoyaltyProgramKeyRepositoryInterface $loyaltyRepository
    ) {}

    /**
     * Создать или обновить ключ лояльности по user_id (PUT style)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeKey(int $userId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'samosale_key' => 'required|string|max:255'
        ]);

        try {
            $loyaltyData = ['samosale_key' => $data['samosale_key']];

            $loyaltyKey = $this->loyaltyRepository->createOrUpdateByUserId($userId, $loyaltyData);

            $wasRecentlyCreated = $loyaltyKey->wasRecentlyCreated;

            return response()->json([
                'success' => true,
                'message' => $wasRecentlyCreated
                    ? 'Loyalty program key created successfully'
                    : 'Loyalty program key updated successfully',
                'action' => $wasRecentlyCreated ? 'created' : 'updated',
                'data' => [
                    'id' => $loyaltyKey->id,
                    'user_id' => $loyaltyKey->user_id,
                    'samosale_key' => $loyaltyKey->samosale_key,
                    'created_at' => $loyaltyKey->created_at,
                    'updated_at' => $loyaltyKey->updated_at,
                ]
            ], $wasRecentlyCreated ? 201 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save loyalty program key',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getKeyByUserId(int $userId): JsonResponse
    {
        $loyaltyKey = $this->loyaltyRepository->findByUserId($userId);

        if (!$loyaltyKey) {
            return response()->json(['message' => 'Key not found'], 404);
        }

        return response()->json([
            'samosale_key' => $loyaltyKey->getAttribute('samosale_key'),
        ]);
    }
}
