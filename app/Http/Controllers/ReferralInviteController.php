<?php

namespace App\Http\Controllers;

use App\Models\ReferralInvite;
use App\Models\Client;

// добавляем модель Client для проверки
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReferralInviteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $invites = ReferralInvite::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $invites
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'referral_code' => 'required|string',
            'telegram_id' => 'required|integer|unique:referral_invites,telegram_id',
        ], [
            'referral_code.required' => 'Реферальный код обязателен',
            'telegram_id.required' => 'Telegram ID обязателен',
            'telegram_id.unique' => 'Для этого Telegram ID уже существует инвайт',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибки валидации',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Проверяем, что клиент еще не зарегистрирован
        $clientExists = Client::where('telegram_id', $request->telegram_id)->exists();
        if ($clientExists) {
            return response()->json([
                'success' => false,
                'message' => 'Клиент с таким Telegram ID уже зарегистрирован'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Проверяем, что referral_code существует у кого-то из клиентов
        if (!ReferralInvite::isValidReferralCode($request->referral_code)) {
            return response()->json([
                'success' => false,
                'message' => 'Реферальный код не найден среди зарегистрированных клиентов'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $invite = ReferralInvite::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Реферальный инвайт успешно создан',
                'data' => $invite->load('referrer')
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании инвайта: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ReferralInvite $referralInvite): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $referralInvite
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReferralInvite $referralInvite): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'referral_code' => [
                'sometimes',
                'string',
            ],
            'telegram_id' => [
                'sometimes',
                'integer',
                Rule::unique('referral_invites')->ignore($referralInvite->id)
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибки валидации',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Если обновляется telegram_id, проверяем что клиент не зарегистрирован
        if ($request->has('telegram_id')) {
            $clientExists = Client::where('telegram_id', $request->telegram_id)->exists();
            if ($clientExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Клиент с таким Telegram ID уже зарегистрирован'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // Если обновляется referral_code, проверяем что он существует у клиентов
        if ($request->has('referral_code') && !ReferralInvite::isValidReferralCode($request->referral_code)) {
            return response()->json([
                'success' => false,
                'message' => 'Реферальный код не найден среди зарегистрированных клиентов'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $referralInvite->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Реферальный инвайт успешно обновлен',
                'data' => $referralInvite->fresh('referrer')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении инвайта: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReferralInvite $referralInvite): JsonResponse
    {
        try {
            $referralInvite->delete();

            return response()->json([
                'success' => true,
                'message' => 'Реферальный инвайт успешно удален'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении инвайта: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Поиск инвайта по referral_code
     */
    public function findByCode(string $referralCode): JsonResponse
    {
        $invite = ReferralInvite::where('referral_code', $referralCode)->first();

        if (!$invite) {
            return response()->json([
                'success' => false,
                'message' => 'Инвайт с таким кодом не найден'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $invite
        ]);
    }

    /**
     * Проверка существования инвайта по telegram_id
     */
    public function checkByTelegramId(int $telegramId): JsonResponse
    {
        $invite = ReferralInvite::where('telegram_id', $telegramId)->first();
        $clientExists = Client::where('telegram_id', $telegramId)->exists();

        return response()->json([
            'success' => true,
            'invite_exists' => !is_null($invite),
            'client_registered' => $clientExists,
            'message' => $clientExists ?
                'Клиент уже зарегистрирован' :
                ($invite ? 'Инвайт найден' : 'Инвайт не найден'),
            'data' => $invite
        ]);
    }

    /**
     * Удаление инвайта по telegram_id (для использования при регистрации)
     */
    public function deleteByTelegramId(int $telegramId): JsonResponse
    {
        try {
            $deleted = ReferralInvite::where('telegram_id', $telegramId)->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Реферальный инвайт успешно удален'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Инвайт не найден'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении инвайта: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
