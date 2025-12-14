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
        'balance',
        'bonus_balance', // бонусные баллы
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
        'balance' => 'decimal:2',
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
     * Relationship: Custom fields for this client
     */
    public function customFields(): HasMany
    {
        return $this->hasMany(CustomClientField::class, 'client_id', 'user_id');
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
     * Get custom field value by name
     */
    public function getCustomField(string $fieldName): ?string
    {
        $field = $this->customFields()->where('field_name', $fieldName)->first();
        return $field ? $field->field_value : null;
    }

    /**
     * Set or update custom field
     */
    public function setCustomField(string $fieldName, string $fieldValue, string $fieldType = 'text'): void
    {
        $this->customFields()->updateOrCreate(
            ['field_name' => $fieldName],
            ['field_value' => $fieldValue, 'field_type' => $fieldType]
        );
    }

    /**
     * Get all custom fields as key-value array
     */
    public function getCustomFieldsArray(): array
    {
        return $this->customFields->pluck('field_value', 'field_name')->toArray();
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

    public static function booted()
    {
        static::created(function ($client) {
            ReferralInvite::where('telegram_id', $client->telegram_id)->delete();
        });

        static::deleting(function ($client) {
            $client->bonusOperations()->delete();
            $client->customFields()->delete();
            $client->transactions()->delete();
            $client->payments()->delete();
            $client->referralInvites()->delete();
            $client->referrals()->update(['referred_by' => null]);
            $client->rentals()->delete();
        });
    }

    /**
     * Отношение к реферальным инвайтам через referral_code
     */
    public function referralInvites()
    {
        return $this->hasMany(ReferralInvite::class, 'referral_code', 'referral_code');
    }

    /**
     * Отношение к реферальным инвайтам как реферер
     */
    public function referralInvitesAsReferrer()
    {
        return $this->hasMany(ReferralInvite::class, 'referral_code', 'referral_code');
    }

    public function rentals()
    {
        return $this->hasMany(Rental::class, 'client_id', 'user_id');
    }

    public function activeRentals()
    {
        return $this->rentals()->active();
    }

    // Пополнение баланса
    public function addToBalance($amount)
    {
        $this->balance += $amount;
        return $this->save();
    }

    // Списание с баланса
    public function deductFromBalance($amount)
    {
        if ($this->balance >= $amount) {
            $this->balance -= $amount;
            return $this->save();
        }
        return false;
    }

    public function scopeWithoutActiveRentals($query)
    {
        return $query->whereDoesntHave('rentals', function ($query) {
            $query->where('status', 'active');
        });
    }

    // Получить полное имя из кастомных полей
    public function getFullNameAttribute()
    {
        if (!$this->relationLoaded('customFields')) {
            return $this->name;
        }

        $lastName = $this->getCustomFieldValue('last_name');
        $firstName = $this->getCustomFieldValue('first_name');
        $middleName = $this->getCustomFieldValue('middle_name');

        $parts = array_filter([$lastName, $firstName, $middleName]);
        return implode(' ', $parts) ?: $this->name;
    }

    // Получить отдельные компоненты имени
    public function getFirstNameAttribute()
    {
        return $this->getCustomFieldValue('first_name');
    }

    public function getLastNameAttribute()
    {
        return $this->getCustomFieldValue('last_name');
    }

    public function getMiddleNameAttribute()
    {
        return $this->getCustomFieldValue('middle_name');
    }

    public function bonusOperations()
    {
        return $this->hasMany(BonusOperation::class, 'client_id', 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'client_id', 'user_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'client_id', 'user_id');
    }

    /**
     * Get available bonus balance (excluding expired bonuses)
     */
    public function getAvailableBonusBalance(): float
    {
        $availableBonuses = $this->bonusOperations()
            ->accruals()
            ->available()
            ->get();

        return $availableBonuses->sum(function ($bonus) {
            return $bonus->getAvailableAmount();
        });
    }

    /**
     * Get expired bonus amount that should be burned
     */
    public function getExpiredBonusAmount(): float
    {
        $expiredBonuses = $this->bonusOperations()
            ->accruals()
            ->expired()
            ->get();

        return $expiredBonuses->sum(function ($bonus) {
            return $bonus->getAvailableAmount();
        });
    }
}
