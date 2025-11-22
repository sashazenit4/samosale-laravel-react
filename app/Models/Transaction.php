<?php
// app/Models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'client_id',
        'bank_transaction_id',
        'qr_code_id',
        'amount',
        'status',
        'type',
        'description',
        'bank_request',
        'bank_response',
        'qr_code_url',
        'expires_at',
        'paid_at',
    ];

    // ДОБАВЬТЕ ЗНАЧЕНИЯ ПО УМОЛЧАНИЮ
    protected $attributes = [
        'status' => 'pending',
        'type' => 'payment',
        'amount' => 0,
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bank_request' => 'array',
        'bank_response' => 'array',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Отношение к платежу
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Отношение к клиенту
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'user_id');
    }

    /**
     * Scope для незавершенных транзакций
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    /**
     * Scope для успешных транзакций
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope для транзакций по клиенту
     */
    public function scopeForClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Проверка, можно ли создать транзакцию для платежа
     */
    public static function canCreateForPayment(Payment $payment): bool
    {
        return in_array($payment->status, ['partially_paid', 'unpaid'])
            && $payment->total_amount > $payment->paid_amount;
    }

    /**
     * Получить доступную для оплаты сумму
     */
    public function getAvailableAmount(): float
    {
        return (float) ($this->payment->total_amount - $this->payment->paid_amount);
    }

    /**
     * Проверка истечения срока
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Обновление статуса при истечении срока
     */
    public function markAsExpired(): bool
    {
        if ($this->isExpired() && $this->status === 'pending') {
            return $this->update(['status' => 'expired']);
        }
        return false;
    }

    /**
     * Boot метод для установки значений по умолчанию
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            // Убедимся, что статус и тип установлены
            if (is_null($transaction->status)) {
                $transaction->status = 'pending';
            }
            if (is_null($transaction->type)) {
                $transaction->type = 'payment';
            }
        });
    }
}
