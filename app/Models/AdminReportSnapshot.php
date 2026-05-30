<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminReportSnapshot extends Model
{
    protected $fillable = [
        'report_date',
        'captured_at',
        'new_paid_members_count',
        'activation_count',
        'gross_sales_vnd',
        'affiliate_commission_vnd',
        'vat_vnd',
        'company_revenue_vnd',
        'pool_share_in_vnd',
        'pool_share_distributed_vnd',
        'shared_pool_balance_vnd',
        'pool_group_stats',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'captured_at' => 'datetime',
            'pool_group_stats' => 'array',
        ];
    }

    public function logs()
    {
        return $this->hasMany(AdminReportSnapshotLog::class, 'snapshot_id');
    }

    public function poolShareRows()
    {
        return $this->hasMany(AdminReportSnapshotPoolShareRow::class, 'snapshot_id');
    }
}
