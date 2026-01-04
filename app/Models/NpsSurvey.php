<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NpsSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'rental_id',
        'sent_at',
        'completed_at',
        'score',
        'status',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'score' => 'integer',
    ];

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_SENT = 'sent';
    public const STATUS_COMPLETED = 'completed';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_SCHEDULED => 'Запланирована отправка',
            self::STATUS_SENT => 'Отправлено',
            self::STATUS_COMPLETED => 'Опрос завершён',
        ];
    }

    /**
     * Relationship: Client who this survey belongs to
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'user_id');
    }

    /**
     * Relationship: Rental this survey is related to
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    /**
     * Check if survey is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Check if survey is sent
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if survey is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
