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
        $welcomeBonus = BonusSystemConfig::getWelcomeBonus();
        return response()->json([
            'welcome_bonus' => $welcomeBonus
        ]);
    }

    /**
     * Получить информацию о приветственном бонусе с деталями сгорания
     */
    public function getWelcomeBonusExpirationInfo(Request $request): JsonResponse
    {
        $request->validate([
            'award_date' => 'nullable|date'
        ]);

        $awardDate = $request->award_date ? \Carbon\Carbon::parse($request->award_date) : null;
        $info = BonusSystemConfig::getWelcomeBonusWithExpirationInfo($awardDate);

        return response()->json([
            'welcome_bonus_expiration_info' => $info
        ]);
    }

    /**
     * Получить оставшиеся дни до сгорания приветственного бонуса
     */
    public function getWelcomeBonusRemainingDays(Request $request): JsonResponse
    {
        $request->validate([
            'award_date' => 'required|date'
        ]);

        $awardDate = \Carbon\Carbon::parse($request->award_date);
        $remainingDays = BonusSystemConfig::getWelcomeBonusRemainingDays($awardDate);

        return response()->json([
            'award_date' => $awardDate->toDateString(),
            'remaining_days' => $remainingDays,
            'is_expired' => $remainingDays <= 0
        ]);
    }

    /**
     * Проверить истек ли срок действия приветственного бонуса
     */
    public function checkWelcomeBonusExpiration(Request $request): JsonResponse
    {
        $request->validate([
            'award_date' => 'required|date'
        ]);

        $awardDate = \Carbon\Carbon::parse($request->award_date);
        $isExpired = BonusSystemConfig::isWelcomeBonusExpired($awardDate);
        $expirationDate = BonusSystemConfig::getWelcomeBonusExpirationDate($awardDate);

        return response()->json([
            'award_date' => $awardDate->toDateString(),
            'expiration_date' => $expirationDate->toDateString(),
            'is_expired' => $isExpired,
            'total_valid_days' => BonusSystemConfig::getWelcomeBonusExpirationDays()
        ]);
    }

    /**
     * Получить дату сгорания приветственного бонуса
     */
    public function getWelcomeBonusExpirationDate(Request $request): JsonResponse
    {
        $request->validate([
            'award_date' => 'nullable|date'
        ]);

        $awardDate = $request->award_date ? \Carbon\Carbon::parse($request->award_date) : null;
        $expirationDate = BonusSystemConfig::getWelcomeBonusExpirationDate($awardDate);

        return response()->json([
            'award_date' => $awardDate ? $awardDate->toDateString() : now()->toDateString(),
            'expiration_date' => $expirationDate->toDateString(),
            'expiration_days' => BonusSystemConfig::getWelcomeBonusExpirationDays()
        ]);
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
