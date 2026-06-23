<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'gateway',
        'gateway_transaction_id',
        'order_code',
        'amount',
        'transaction_type',
        'status',
        'raw_payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'raw_payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
