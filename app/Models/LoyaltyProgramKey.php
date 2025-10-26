<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static \Illuminate\Database\Eloquent\Builder where($column, $operator = null, $value = null)
 * @method static self|null find($id)
 * @method static self create(array $attributes)
 * @method static self|null first()
 * @method bool update(array $attributes)
 * @method bool delete()
 */
class LoyaltyProgramKey extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'samosale_key',
    ];

    /**
     * Отношение к пользователю
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
