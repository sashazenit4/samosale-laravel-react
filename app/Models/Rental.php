<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rental extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'bike_id',
        'tariff_id',
        'battery_capacity',
        'batteries_count',
        'start_date',
        'planned_end_date',
        'actual_end_date',
        'total_cost',
        'paid_amount',
        'paid_status',
        'status',
        'completion_type',
        'refund_amount',
        'note'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'planned_end_date' => 'datetime',
        'actual_end_date' => 'datetime',
        'total_cost' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'user_id');
    }

    public function bike()
    {
        return $this->belongsTo(Bike::class);
    }

    public function tariff()
    {
        return $this->belongsTo(Tariff::class);
    }

    // Scope для активных аренд
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Scope для завершенных аренд
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'completed_early', 'cancelled']);
    }

    // Проверка, активна ли аренда
    public function isActive()
    {
        return $this->status === 'active';
    }

    // Проверка, можно ли завершить досрочно
    public function canCompleteEarly()
    {
        return $this->isActive() && now()->lt($this->planned_end_date);
    }

    // Добавляем отношение к платежам
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
