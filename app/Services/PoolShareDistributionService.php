<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PoolShareDistributionService
{
    public function __construct(
        private readonly WalletLedgerService $ledger,
        private readonly ReferralCommissionService $referrals,
    ) {
    }

    public function distribute(?Carbon $date = null): array
    {
        $date ??= now();
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();
        $dateLabel = $date->format('d/m/Y');
        $memo = "Chi lại Pool Share {$dateLabel}";

        return DB::transaction(function () use ($start, $end, $memo) {
            $sharedPool = $this->ledger->systemWallet('shared_pool');
            $alreadyDistributed = $sharedPool->ledgerEntries()
                ->where('type', 'pool_share_distribution_out')
                ->where('memo', $memo)
                ->exists();

            if ($alreadyDistributed) {
                return ['status' => 'skipped', 'pool_total' => 0, 'paid_total' => 0, 'groups' => []];
            }

            $poolTotal = (int) $sharedPool->ledgerEntries()
                ->where('type', 'payment_shared_pool')
                ->where('amount_vnd', '>', 0)
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount_vnd');

            if ($poolTotal <= 0) {
                return ['status' => 'empty', 'pool_total' => 0, 'paid_total' => 0, 'groups' => []];
            }

            $eligibleUsers = User::query()
                ->whereHas('subscriptions', function ($query) use ($end) {
                    $query->where('status', 'active')
                        ->where('starts_at', '<=', $end)
                        ->where('ends_at', '>', $end);
                })
                ->get()
                ->groupBy(fn (User $user) => $this->referrals->poolShareGroup($user, $end))
                ->filter(fn ($users, $group) => $group !== '');

            $paidTotal = 0;
            $summary = [];

            foreach (config('quantum.pool_share_groups', []) as $group => $rule) {
                $users = $eligibleUsers->get($group, collect());
                $count = $users->count();

                if ($count === 0) {
                    $summary[$group] = ['users' => 0, 'amount_each' => 0, 'paid_total' => 0];
                    continue;
                }

                $groupTotal = intdiv($poolTotal * (int) $rule['share_bp'], 10000);
                $amountEach = intdiv($groupTotal, $count);

                if ($amountEach <= 0) {
                    $summary[$group] = ['users' => $count, 'amount_each' => 0, 'paid_total' => 0];
                    continue;
                }

                foreach ($users as $user) {
                    $this->ledger->credit($this->ledger->walletForUser($user), $amountEach, 'pool_share_payout', null, $memo);
                    $paidTotal += $amountEach;
                }

                $summary[$group] = [
                    'users' => $count,
                    'amount_each' => $amountEach,
                    'paid_total' => $amountEach * $count,
                ];
            }

            if ($paidTotal > 0) {
                $this->ledger->debit($sharedPool, $paidTotal, 'pool_share_distribution_out', null, $memo);
            }

            return ['status' => 'paid', 'pool_total' => $poolTotal, 'paid_total' => $paidTotal, 'groups' => $summary];
        });
    }
}
