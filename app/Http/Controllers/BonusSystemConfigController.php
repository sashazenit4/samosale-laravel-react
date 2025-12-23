<?php

namespace App\Http\Controllers;

use App\Models\BonusSystemConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BonusSystemConfigController extends Controller
{
    /**
     * Получить все настройки
     */
    public function index(): JsonResponse
    {
        $configs = BonusSystemConfig::all();
        return response()->json($configs);
    }

    /**
     * Получить настройку по ключу
     */
    public function show(string $key): JsonResponse
    {
        $config = BonusSystemConfig::where('key', $key)->first();

        if (!$config) {
            return response()->json([
                'message' => 'Config not found'
            ], 404);
        }

        return response()->json($config);
    }

    /**
     * Создать новую настройку
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:bonus_system_configs,key',
            'value' => 'required|array',
            'description' => 'nullable|string'
        ]);

        $config = BonusSystemConfig::create($validated);

        return response()->json($config, 201);
    }

    /**
     * Обновить настройку
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $config = BonusSystemConfig::where('key', $key)->first();

        if (!$config) {
            return response()->json([
                'message' => 'Config not found'
            ], 404);
        }

        $validated = $request->validate([
            'value' => 'required|array',
            'description' => 'nullable|string'
        ]);

        $config->update($validated);

        return response()->json($config);
    }

    /**
     * Удалить настройку
     */
    public function destroy(string $key): JsonResponse
    {
        $config = BonusSystemConfig::where('key', $key)->first();

        if (!$config) {
            return response()->json([
                'message' => 'Config not found'
            ], 404);
        }

        $config->delete();

        return response()->json(null, 204);
    }

    /**
     * Получить конкретные бизнес-настройки
     */
    public function getWelcomeBonus(): JsonResponse
    {
        $amount = BonusSystemConfig::getWelcomeBonus();
        return response()->json(['welcome_bonus_amount' => $amount]);
    }

    public function getReferralBonus(): JsonResponse
    {
        $bonus = BonusSystemConfig::getReferralBonus();
        return response()->json($bonus);
    }

    public function getPaymentBonusPercentage(): JsonResponse
    {
        $percentage = BonusSystemConfig::getPaymentBonusPercentage();
        return response()->json(['payment_bonus_percentage' => $percentage]);
    }

    public function getBonusLevels(): JsonResponse
    {
        $levels = BonusSystemConfig::getBonusLevels();
        return response()->json(['bonus_levels' => $levels]);
    }

    public function getClientLevel(Request $request): JsonResponse
    {
        $request->validate([
            'total_spent' => 'required|numeric|min:0'
        ]);

        $level = BonusSystemConfig::getClientLevel($request->total_spent);
        return response()->json([
            'total_spent' => $request->total_spent,
            'client_level' => $level
        ]);
    }

    /**
     * Получить время жизни бонуса
     */
    public function getBonusLifetimeDays(): JsonResponse
    {
        $days = BonusSystemConfig::getBonusLifetimeDays();
        return response()->json(['bonus_lifetime_days' => $days]);
    }

    /**
     * Получить условие для реферального бонуса
     */
    public function getReferralBonusCondition(): JsonResponse
    {
        $condition = BonusSystemConfig::getReferralBonusCondition();
        return response()->json(['referral_bonus_condition' => $condition]);
    }

    /**
     * Проверить условие для реферального бонуса
     */
    public function checkReferralBonusCondition(Request $request): JsonResponse
    {
        $request->validate([
            'referee_total_spent' => 'required|numeric|min:0'
        ]);

        $isConditionMet = BonusSystemConfig::isReferralBonusConditionMet($request->referee_total_spent);
        $minSpent = BonusSystemConfig::getReferralMinSpent();

        return response()->json([
            'referee_total_spent' => $request->referee_total_spent,
            'min_required_spent' => $minSpent,
            'is_condition_met' => $isConditionMet
        ]);
    }
}
