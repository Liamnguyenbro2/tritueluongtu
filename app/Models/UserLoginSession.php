<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLoginSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'device_name',
        'user_agent',
        'ip_address',
        'login_at',
        'last_seen_at',
        'idle_expires_at',
        'absolute_expires_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'login_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'idle_expires_at' => 'datetime',
            'absolute_expires_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
