<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'telegram_id',
        'phone_number',
        'name',
        'registration_date',
        'referral_code',
        'referred_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'registration_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registration_date' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relationship: User who referred this client
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'referred_by', 'user_id');
    }

    /**
     * Relationship: Clients referred by this user
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Client::class, 'referred_by', 'user_id');
    }

    /**
     * Scope: Find by Telegram ID
     */
    public function scopeByTelegramId($query, $telegramId)
    {
        return $query->where('telegram_id', $telegramId);
    }

    /**
     * Scope: Find by referral code
     */
    public function scopeByReferralCode($query, $referralCode)
    {
        return $query->where('referral_code', $referralCode);
    }

    /**
     * Scope: Find by phone number
     */
    public function scopeByPhoneNumber($query, $phoneNumber)
    {
        return $query->where('phone_number', $phoneNumber);
    }

    /**
     * Check if user has referrals
     */
    public function hasReferrals(): bool
    {
        return $this->referrals()->exists();
    }

    /**
     * Generate a unique referral code
     */
    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }
}
