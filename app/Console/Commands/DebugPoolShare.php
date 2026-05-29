<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ReferralCommissionService;
use App\Services\WalletLedgerService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DebugPoolShare extends Command
{
    protected $signature = 'pool-share:debug
        {date? : Ngay can kiem tra, dinh dang YYYY-MM-DD}
        {--user_id= : Kiem tra chi tiet mot user theo ID}
        {--email= : Kiem tra chi tiet mot user theo email}';

    protected $description = 'In ra ly do Pool Share co/khong duoc chia trong ngay.';

    public function handle(
        WalletLedgerService $ledger,
        ReferralCommissionService $referrals,
    ): int {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : now();
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $memo = 'Chi láº¡i Pool Share '.$date->format('d/m/Y');

        $sharedPool = $ledger->systemWallet('shared_pool');
        $alreadyDistributed = $sharedPool->ledgerEntries()
            ->where('type', 'pool_share_distribution_out')
            ->where('memo', $memo)
            ->exists();

        $poolEntries = $sharedPool->ledgerEntries()
            ->where('type', 'payment_shared_pool')
            ->where('amount_vnd', '>', 0)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at');

        $poolTotal = (int) (clone $poolEntries)->sum('amount_vnd');
        $poolCount = (clone $poolEntries)->count();

        $this->info('POOL SHARE DEBUG');
        $this->line('Ngay check: '.$date->toDateString());
        $this->line('Khung tinh: '.$start->toDateTimeString().' -> '.$end->toDateTimeString());
        $this->line('Timezone app: '.config('app.timezone'));
        $this->line('Lich chay scheduler: dailyAt(23:59)');
        $this->line('Da chia ngay nay: '.($alreadyDistributed ? 'YES' : 'NO'));
        $this->line('So du vi shared_pool hien tai: '.$sharedPool->balance_vnd);
        $this->line('Tong payment_shared_pool trong ngay: '.$poolTotal.' VND');
        $this->line('So but toan payment_shared_pool trong ngay: '.$poolCount);
        $this->newLine();

        if ($poolCount > 0) {
            $this->table(
                ['Entry ID', 'Created At', 'Amount', 'Memo'],
                (clone $poolEntries)->get(['id', 'created_at', 'amount_vnd', 'memo'])->map(fn ($entry) => [
                    $entry->id,
                    $entry->created_at?->toDateTimeString(),
                    $entry->amount_vnd,
                    $entry->memo,
                ])->all()
            );
        } else {
            $this->warn('Khong co dong payment_shared_pool nao trong ngay nay.');
            $this->line('Neu vi shared_pool co tien tu ngay cu, lenh chia van KHONG su dung so du do.');
            $this->line('Code hien tai chi chia tren tong payment_shared_pool duoc ghi nhan trong chinh ngay dang check.');
        }

        $eligibleUsers = User::query()
            ->when($this->option('user_id'), fn ($query, $userId) => $query->whereKey($userId))
            ->when($this->option('email'), fn ($query, $email) => $query->where('email', $email))
            ->whereHas('subscriptions', function ($query) use ($end) {
                $query->where('status', 'active')
                    ->where('starts_at', '<=', $end)
                    ->where('ends_at', '>', $end);
            })
            ->orderBy('id')
            ->get();

        $this->newLine();
        $this->info('USER DUOC XET CHIA');
        $this->line('Tong user co subscription active tai thoi diem cuoi ngay: '.$eligibleUsers->count());

        if ($eligibleUsers->isEmpty()) {
            $this->warn('Khong co user nao qua duoc dieu kien subscription active.');
        } else {
            $rows = [];

            foreach ($eligibleUsers as $user) {
                [$cycleStart, $cycleEnd] = $referrals->currentCycleWindow($user, $end);
                $referralCount = $referrals->activeReferralCount($user, $end);
                $group = $referrals->poolShareGroup($user, $end);

                $rows[] = [
                    'id' => $user->id,
                    'email' => $user->email,
                    'trial_started_at' => optional($user->trial_started_at)->toDateString(),
                    'cycle_start' => $cycleStart->toDateString(),
                    'cycle_end' => $cycleEnd->toDateString(),
                    'active_referrals' => $referralCount,
                    'group' => $group ?: 'NONE',
                    'reason' => $group ? 'Du dieu kien' : 'Khong dat min referral theo nhom',
                ];
            }

            $this->table(
                ['User ID', 'Email', 'Trial Start', 'Cycle Start', 'Cycle End', 'Active Referrals', 'Group', 'Reason'],
                $rows
            );
        }

        $this->newLine();
        $this->info('CAU HINH NHOM');
        $groupRows = collect(config('quantum.pool_share_groups', []))
            ->map(fn ($rule, $group) => [
                'group' => $group,
                'min' => $rule['min'],
                'max' => $rule['max'] ?? 'null',
                'share_bp' => $rule['share_bp'],
            ])->values()->all();
        $this->table(['Group', 'Min', 'Max', 'Share BP'], $groupRows);

        $this->newLine();
        $this->info('GOI Y KIEM TRA TREN HOSTING');
        $this->line('1. php artisan pool-share:debug '.$date->toDateString());
        $this->line('2. php artisan pool-share:distribute '.$date->toDateString());
        $this->line('3. php artisan schedule:list');
        $this->line('4. Kiem tra cron hosting co goi `php artisan schedule:run` moi phut hay khong.');

        return self::SUCCESS;
    }
}
