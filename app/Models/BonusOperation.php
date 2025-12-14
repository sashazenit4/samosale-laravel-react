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
        'type',
        'description',
        'metadata',
        'expires_at',
        'is_burnable',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
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
