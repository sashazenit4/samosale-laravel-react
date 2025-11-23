<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_code',
        'telegram_id'
    ];

    protected $casts = [
        'telegram_id' => 'integer',
    ];

    /**
     * Отношение к клиенту-рефереру
     */
    public function referrer()
    {
        return $this->belongsTo(Client::class, 'referral_code', 'referral_code');
    }

    /**
     * Получить ID реферера по telegram_id приглашенного
     */
    public static function getReferredByFromInvites(int $telegramId): ?int
    {
        $invite = self::with('referrer')->where('telegram_id', $telegramId)->first();

        return $invite && $invite->referrer ? $invite->referrer->id : null;
    }

    /**
     * Проверяет, можно ли создать инвайт для данного telegram_id
     */
    public static function canCreateForTelegramId($telegramId): bool
    {
        // Нельзя создать инвайт если клиент уже зарегистрирован
        return !Client::where('telegram_id', $telegramId)->exists();
    }

    /**
     * Проверяет, существует ли referral_code у кого-то из клиентов
     */
    public static function isValidReferralCode($referralCode): bool
    {
        return Client::where('referral_code', $referralCode)->exists();
    }
}
