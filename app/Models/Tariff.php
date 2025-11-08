<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tariff extends Model
{
    use HasFactory;

    protected $fillable = [
        'program',
        'power',
        'price_month',
        'price_week1',
        'price_week2',
        'price_week3',
        'price_week4',
        'is_active',
    ];

    protected $casts = [
        'price_month' => 'decimal:2',
        'price_week1' => 'decimal:2',
        'price_week2' => 'decimal:2',
        'price_week3' => 'decimal:2',
        'price_week4' => 'decimal:2',
        'is_active' => 'boolean',
        'power' => 'integer',
    ];

    /**
     * Scope: Active tariffs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By program
     */
    public function scopeByProgram($query, $program)
    {
        return $query->where('program', $program);
    }

    /**
     * Scope: By power
     */
    public function scopeByPower($query, $power)
    {
        return $query->where('power', $power);
    }

    /**
     * Check if tariff is available for program and power
     */
    public static function existsForProgramAndPower(string $program, int $power): bool
    {
        return self::where('program', $program)
            ->where('power', $power)
            ->active()
            ->exists();
    }
}
