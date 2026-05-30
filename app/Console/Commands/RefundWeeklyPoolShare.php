<?php

namespace App\Console\Commands;

use App\Services\PoolShareWeeklyRefundService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RefundWeeklyPoolShare extends Command
{
    protected $signature = 'pool-share:refund-weekly {date? : Ngày cần hoàn trả, định dạng YYYY-MM-DD}';

    protected $description = 'Hoàn trả số dư Pool Share còn lại cuối tuần về ví Admin.';

    public function handle(PoolShareWeeklyRefundService $refundService): int
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : now();
        $result = $refundService->refund($date);

        $this->info("Pool Share weekly refund {$result['status']}: {$result['amount_vnd']} đ.");

        return self::SUCCESS;
    }
}
