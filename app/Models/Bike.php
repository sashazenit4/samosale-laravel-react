<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bike extends Model
{
    use HasFactory;

    protected $fillable = [
        'bike_number',
        'frame_number',
        'status',
        'type'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    public const STATUS_RENTING = 'renting';
    public const STATUS_FREE = 'free';
    public const STATUS_STOLEN = 'stolen';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_RENTING => 'В аренде',
            self::STATUS_FREE => 'Свободен',
            self::STATUS_STOLEN => 'Украден',
        ];
    }

    public function isRenting(): bool
    {
        return $this->status === self::STATUS_RENTING;
    }

    public function isFree(): bool
    {
        return $this->status === self::STATUS_FREE;
    }

    public function isStolen(): bool
    {
        return $this->status === self::STATUS_STOLEN;
    }
}
