<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PoolShareWeeklyRefundService
{
    public function __construct(
        private readonly WalletLedgerService $ledger,
    ) {
    }

    public function refund(?Carbon $date = null): array
    {
        $date ??= now();
        $memo = 'Hoàn trả Pool Share còn lại dư tuần '.$date->format('d/m/Y');

        return DB::transaction(function () use ($memo) {
            $sharedPool = $this->ledger->systemWallet('shared_pool');
            $adminWallet = $this->ledger->systemWallet('admin');

            $alreadyRefunded = $sharedPool->ledgerEntries()
                ->where('type', 'pool_share_weekly_refund_out')
                ->where('memo', $memo)
                ->exists();

            if ($alreadyRefunded) {
                return ['status' => 'skipped', 'amount_vnd' => 0];
            }

            $balance = (int) $sharedPool->fresh()->balance_vnd;

            if ($balance <= 0) {
                return ['status' => 'empty', 'amount_vnd' => 0];
            }

            $this->ledger->debit($sharedPool, $balance, 'pool_share_weekly_refund_out', null, $memo);
            $this->ledger->credit($adminWallet, $balance, 'pool_share_weekly_refund_in', null, $memo);

            return ['status' => 'refunded', 'amount_vnd' => $balance];
        });
    }
}
