<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminReportSnapshotLog extends Model
{
    protected $fillable = [
        'snapshot_id',
        'ledger_entry_id',
        'log_type',
        'amount_vnd',
        'memo',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function snapshot()
    {
        return $this->belongsTo(AdminReportSnapshot::class, 'snapshot_id');
    }
}
