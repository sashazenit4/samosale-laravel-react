<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'month',
        'status',
        'year',
        'generated_at',
        'paid_at',
        'total_amount',
        'paid_amount',
        'payment_type',
        'client_id',
        'article',
        'purpose',
        'rental_id',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'paid_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    /**
     * Отношение к клиенту
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'user_id');
    }

    /**
     * Отношение к аренде
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    /**
     * Автоматическое обновление статуса при изменении paid_amount
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($payment) {
            // Обновляем статус на основе оплаченной суммы
            if ($payment->paid_amount >= $payment->total_amount) {
                $payment->status = 'paid';
                $payment->paid_at = now();
            } elseif ($payment->paid_amount > 0) {
                $payment->status = 'partially_paid';
            } else {
                $payment->status = 'unpaid';
            }

            // Если оплата полная и paid_at не установлен, устанавливаем текущее время
            if ($payment->status === 'paid' && !$payment->paid_at) {
                $payment->paid_at = now();
            }
        });
    }

    /**
     * Scope для фильтрации по статусу
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope для фильтрации по году и месяцу
     */
    public function scopePeriod($query, $year, $month = null)
    {
        $query->where('year', $year);
        if ($month) {
            $query->where('month', $month);
        }
        return $query;
    }

    /**
     * Scope для фильтрации по клиенту
     */
    public function scopeClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }
}
