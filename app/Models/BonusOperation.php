<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'transaction_id',
        'amount',
        'used_amount',
        'type',
        'description',
        'metadata',
        'expires_at',
        'is_burnable',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'is_burnable' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'user_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Check if this bonus operation is expired
     */
    public function isExpired(): bool
    {
        if (!$this->is_burnable || !$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Get the available amount (not used and not expired)
     */
    public function getAvailableAmount(): float
    {
        if ($this->type !== 'accrual') {
            return 0;
        }

        if ($this->isExpired()) {
            return 0;
        }

        return max(0, $this->amount - $this->used_amount);
    }

    /**
     * Check if this bonus has available amount
     */
    public function hasAvailableAmount(): bool
    {
        return $this->getAvailableAmount() > 0;
    }

    /**
     * Use a specific amount from this bonus operation
     */
    public function useAmount(float $amount): bool
    {
        $available = $this->getAvailableAmount();
        
        if ($amount > $available) {
            return false;
        }

        $this->used_amount += $amount;
        return $this->save();
    }

    /**
     * Scope: Get only accrual operations
     */
    public function scopeAccruals($query)
    {
        return $query->where('type', 'accrual');
    }

    /**
     * Scope: Get only burnable bonuses
     */
    public function scopeBurnable($query)
    {
        return $query->where('is_burnable', true);
    }

    /**
     * Scope: Get expired bonuses
     */
    public function scopeExpired($query)
    {
        return $query->where('is_burnable', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    /**
     * Scope: Get available (not expired, not fully used) bonuses
     */
    public function scopeAvailable($query)
    {
        return $query->where('type', 'accrual')
            ->where(function ($q) {
                $q->where('is_burnable', false)
                  ->orWhere(function ($subQ) {
                      $subQ->where('is_burnable', true)
                           ->where(function ($expQ) {
                               $expQ->whereNull('expires_at')
                                    ->orWhere('expires_at', '>=', now());
                           });
                  });
            })
            ->whereRaw('used_amount < amount');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->is_burnable && !$model->expires_at) {
                $model->expires_at = now()->addDays(
                    BonusSystemConfig::getBonusLifetimeDays()
                );
            }
        });

        static::updating(function ($model) {
            if ($model->is_burnable && !$model->expires_at && $model->isDirty('is_burnable')) {
                $model->expires_at = now()->addDays(
                    BonusSystemConfig::getBonusLifetimeDays()
                );
            }
        });
    }
}
