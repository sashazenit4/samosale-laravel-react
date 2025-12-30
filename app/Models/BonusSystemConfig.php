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
     * Получить приветственный бонус
     */
    public static function getWelcomeBonus(): array
    {
        return self::getConfig('welcome_bonus', [
            'amount' => 500,
            'expiration_days' => 60
        ]);
    }

    /**
     * Получить сумму приветственного бонуса
     */
    public static function getWelcomeBonusAmount(): int
    {
        $welcomeBonus = self::getWelcomeBonus();
        return $welcomeBonus['amount'] ?? 500;
    }

    /**
     * Получить количество дней до сгорания приветственного бонуса
     */
    public static function getWelcomeBonusExpirationDays(): int
    {
        $welcomeBonus = self::getWelcomeBonus();
        return $welcomeBonus['expiration_days'] ?? 60;
    }

    /**
     * Получить дату сгорания приветственного бонуса
     */
    public static function getWelcomeBonusExpirationDate(\DateTimeInterface $awardDate = null): \DateTimeInterface
    {
        $awardDate = $awardDate ?? now();
        $expirationDays = self::getWelcomeBonusExpirationDays();

        return $awardDate->copy()->addDays($expirationDays);
    }

    /**
     * Проверить, истек ли срок действия приветственного бонуса
     */
    public static function isWelcomeBonusExpired(\DateTimeInterface $awardDate): bool
    {
        $expirationDate = self::getWelcomeBonusExpirationDate($awardDate);
        return now()->greaterThan($expirationDate);
    }

    /**
     * Получить оставшееся количество дней до сгорания приветственного бонуса
     */
    public static function getWelcomeBonusRemainingDays(\DateTimeInterface $awardDate): int
    {
        if (self::isWelcomeBonusExpired($awardDate)) {
            return 0;
        }

        $expirationDate = self::getWelcomeBonusExpirationDate($awardDate);
        return now()->diffInDays($expirationDate, false);
    }

    /**
     * Получить информацию о приветственном бонусе с деталями сгорания
     */
    public static function getWelcomeBonusWithExpirationInfo(\DateTimeInterface $awardDate = null): array
    {
        $welcomeBonus = self::getWelcomeBonus();
        $awardDate = $awardDate ?? now();

        return array_merge($welcomeBonus, [
            'award_date' => $awardDate,
            'expiration_date' => self::getWelcomeBonusExpirationDate($awardDate),
            'is_expired' => self::isWelcomeBonusExpired($awardDate),
            'remaining_days' => self::getWelcomeBonusRemainingDays($awardDate),
            'total_days' => $welcomeBonus['expiration_days'] ?? 60
        ]);
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


    /**
     * Получить уровень по номеру уровня (level)
     */
    public static function getLevelByNumber(int $levelNumber): ?array
    {
        foreach (self::getBonusLevels() as $level) {
            if ((int)($level['level'] ?? 0) === $levelNumber) {
                return $level;
            }
        }

        return null;
    }

    /**
     * Получить бонусный процент по номеру уровня (level)
     */
    public static function getBonusPercentageByLevel(int $levelNumber): float
    {
        $level = self::getLevelByNumber($levelNumber);

        if ($level && isset($level['bonus_percentage'])) {
            return (float) $level['bonus_percentage'];
        }

        return self::getPaymentBonusPercentage();
    }

    /**
     * Получить имя уровня по номеру уровня (level)
     */
    public static function getLevelNameByNumber(int $levelNumber): ?string
    {
        $level = self::getLevelByNumber($levelNumber);
        return $level['name'] ?? null;
    }


    /**
     * Получить время жизни бонуса в днях
     */
    public static function getBonusLifetimeDays(): int
    {
        return self::getConfig('bonus_lifetime_days')['days'] ?? 30;
    }

    /**
     * Получить условие для получения реферального бонуса
     */
    public static function getReferralBonusCondition(): array
    {
        return self::getConfig('referral_bonus_condition', ['referee_min_spent' => 0]);
    }

    /**
     * Получить минимальную сумму, которую должен потратить реферал
     */
    public static function getReferralMinSpent(): float
    {
        $condition = self::getReferralBonusCondition();
        return $condition['referee_min_spent'] ?? 0;
    }

    /**
     * Проверить, выполнено ли условие для получения реферального бонуса
     */
    public static function isReferralBonusConditionMet(float $refereeTotalSpent): bool
    {
        $minSpent = self::getReferralMinSpent();
        return $refereeTotalSpent >= $minSpent;
    }

    public static function allConfigs(): \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
    {
        return static::all(['id', 'key', 'value', 'description', 'created_at', 'updated_at'])
            ->map(fn($config) => [
                'id'          => $config->id,
                'key'         => $config->key,
                'value'       => $config->value,
                'description' => $config->description ?? '',
                'created_at'  => $config->created_at,
                'updated_at'  => $config->updated_at,
            ]);
    }
}
