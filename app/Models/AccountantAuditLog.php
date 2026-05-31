<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountantAuditLog extends Model
{
    protected $fillable = [
        'actor_user_id',
        'action',
        'target_type',
        'target_id',
        'description',
        'notes',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
