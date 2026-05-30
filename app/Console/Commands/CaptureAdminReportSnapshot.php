<?php

namespace App\Console\Commands;

use App\Services\AdminReportSnapshotService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CaptureAdminReportSnapshot extends Command
{
    protected $signature = 'admin-reports:snapshot {date? : YYYY-MM-DD}';

    protected $description = 'Capture the daily admin report snapshot and prune old snapshots.';

    public function handle(AdminReportSnapshotService $snapshots): int
    {
        $date = $this->argument('date')
            ? Carbon::parse((string) $this->argument('date'))
            : now();

        $snapshot = $snapshots->capture($date);

        $this->info('Admin report snapshot captured for '.$snapshot->report_date->format('d/m/Y').'.');
        $this->line('Sales: '.number_format($snapshot->gross_sales_vnd, 0, ',', '.').' đ');
        $this->line('Pool Share: '.number_format($snapshot->pool_share_in_vnd, 0, ',', '.').' đ');

        return self::SUCCESS;
    }
}
