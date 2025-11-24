<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'transaction_id',
        'amount',
        'type',
        'description',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'user_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
