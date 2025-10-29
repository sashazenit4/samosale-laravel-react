<?php
// app/Models/CustomFieldTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomFieldTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'type',
        'validation_rules',
        'options',
        'is_required',
        'is_active',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scope: Active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    /**
     * Get validation rules for this field
     */
    public function getValidationRule(): array
    {
        $baseRule = [];

        // Базовые правила по типу
        switch ($this->type) {
            case 'email':
                $baseRule[] = 'email';
                break;
            case 'number':
                $baseRule[] = 'numeric';
                break;
            case 'date':
                $baseRule[] = 'date';
                break;
            case 'select':
                if ($this->options) {
                    $baseRule[] = 'in:' . implode(',', $this->options);
                }
                break;
        }

        // Обязательное поле
        if ($this->is_required) {
            array_unshift($baseRule, 'required');
        } else {
            array_unshift($baseRule, 'nullable');
        }

        // Дополнительные правила
        if ($this->validation_rules) {
            $baseRule = array_merge($baseRule, $this->validation_rules);
        }

        return $baseRule;
    }

    /**
     * Check if value is valid for this field type
     */
    public function isValidValue($value): bool
    {
        if ($value === null && !$this->is_required) {
            return true;
        }

        if ($value === null && $this->is_required) {
            return false;
        }

        switch ($this->type) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'number':
                return is_numeric($value);
            case 'date':
                return strtotime($value) !== false;
            case 'select':
                return in_array($value, $this->options ?? []);
            default:
                return is_string($value);
        }
    }
}
