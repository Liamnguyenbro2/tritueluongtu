<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SepayWebhookLog extends Model
{
    protected $fillable = [
        'webhook_uuid',
        'headers',
        'payload',
        'ip_address',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'payload' => 'array',
        ];
    }
}
