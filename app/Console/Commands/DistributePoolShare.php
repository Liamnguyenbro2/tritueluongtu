<?php

namespace App\Console\Commands;

use App\Services\PoolShareDistributionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DistributePoolShare extends Command
{
    protected $signature = 'pool-share:distribute {date? : Ngày cần chốt, định dạng YYYY-MM-DD}';

    protected $description = 'Chia Pool Share hằng ngày cho các nhóm A/B/C đủ điều kiện.';

    public function handle(PoolShareDistributionService $poolShare): int
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : now();
        $result = $poolShare->distribute($date);

        $this->info("Pool Share {$result['status']}: tổng {$result['pool_total']} đ, đã chi {$result['paid_total']} đ.");

        foreach ($result['groups'] as $group => $summary) {
            $this->line("Nhóm {$group}: {$summary['users']} user, mỗi user {$summary['amount_each']} đ.");
        }

        return self::SUCCESS;
    }
}
