<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminReportSnapshotPoolShareRow extends Model
{
    protected $fillable = [
        'snapshot_id',
        'user_id',
        'name',
        'email',
        'group_code',
        'active_referrals_count',
        'payout_vnd',
        'account_status',
        'subscription_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function snapshot()
    {
        return $this->belongsTo(AdminReportSnapshot::class, 'snapshot_id');
    }
}
