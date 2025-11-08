<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'status'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    public const STATUS_STOLEN = 'stolen';
    public const STATUS_FREE = 'free';
    public const STATUS_RENTED = 'rented';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_STOLEN => 'Угон',
            self::STATUS_FREE => 'Свободен',
            self::STATUS_RENTED => 'Аренда',
        ];
    }

    public function isStolen(): bool
    {
        return $this->status === self::STATUS_STOLEN;
    }

    public function isFree(): bool
    {
        return $this->status === self::STATUS_FREE;
    }

    public function isRented(): bool
    {
        return $this->status === self::STATUS_RENTED;
    }
}
