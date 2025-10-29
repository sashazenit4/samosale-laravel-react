<?php
// app/Models/CustomClientField.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomClientField extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'field_name',
        'field_type',
        'field_value',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'user_id');
    }
}
