<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusSystemConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    protected $casts = [
        'value' => 'array'
    ];

    /**
     * Получить настройку по ключу
     */
    public static function getConfig(string $key, $default = null)
    {
        $config = static::where('key', $key)->first();
        return $config ? $config->value : $default;
    }

    /**
     * Установить настройку
     */
    public static function setConfig(string $key, $value, string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description
            ]
        );
    }

    /**
     * Получить бонус за регистрацию
     */
    public static function getWelcomeBonus(): int
    {
        return self::getConfig('welcome_bonus')['amount'] ?? 500;
    }

    /**
     * Получить бонусы за реферала
     */
    public static function getReferralBonus(): array
    {
        return self::getConfig('referral_bonus', ['referrer_amount' => 1500, 'referee_amount' => 1500]);
    }

    /**
     * Получить процент начисления за оплату
     */
    public static function getPaymentBonusPercentage(): float
    {
        return self::getConfig('payment_bonus_percentage')['percentage'] ?? 5;
    }

    /**
     * Получить уровни бонусной системы
     */
    public static function getBonusLevels(): array
    {
        return self::getConfig('bonus_levels', []);
    }

    /**
     * Получить уровень клиента на основе потраченной суммы
     */
    public static function getClientLevel(float $totalSpent): array
    {
        $levels = self::getBonusLevels();
        $clientLevel = $levels[0] ?? []; // Уровень по умолчанию

        foreach ($levels as $level) {
            if ($totalSpent >= ($level['min_spent'] ?? 0)) {
                $clientLevel = $level;
            } else {
                break;
            }
        }

        return $clientLevel;
    }

    /**
     * Получить бонусный процент для клиента
     */
    public static function getClientBonusPercentage(float $totalSpent): float
    {
        $level = self::getClientLevel($totalSpent);
        return $level['bonus_percentage'] ?? self::getPaymentBonusPercentage();
    }
}
