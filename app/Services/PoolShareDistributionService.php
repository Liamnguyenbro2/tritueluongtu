<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PoolShareDistributionService
{
    public function __construct(
        private readonly WalletLedgerService $ledger,
        private readonly ReferralCommissionService $referrals,
        private readonly PoolShareAllocator $allocator,
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
            $allocation = $this->allocator->allocate($poolTotal, $eligibleUsers);
            $summary = $allocation['summary'];

            foreach ($allocation['payouts'] as $payout) {
                if ((int) $payout['amount_vnd'] <= 0) {
                    continue;
                }

                /** @var \App\Models\User $user */
                $user = $payout['recipient'];
                $amount = (int) $payout['amount_vnd'];
                $this->ledger->credit($this->ledger->walletForUser($user), $amount, 'pool_share_payout', null, $memo);
                $paidTotal += $amount;
            }

            if ($paidTotal > 0) {
                $this->ledger->debit($sharedPool, $paidTotal, 'pool_share_distribution_out', null, $memo);
            }

            return ['status' => 'paid', 'pool_total' => $poolTotal, 'paid_total' => $paidTotal, 'groups' => $summary];
        });
    }
}
