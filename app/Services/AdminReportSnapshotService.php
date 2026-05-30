<?php

namespace App\Services;

use App\Models\AdminReportSnapshot;
use App\Models\LedgerEntry;
use App\Models\PaymentOrder;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminReportSnapshotService
{
    public function capture(?Carbon $date = null): AdminReportSnapshot
    {
        $date ??= now();
        $reportDate = $date->copy()->startOfDay();
        $start = $reportDate->copy();
        $end = $reportDate->copy()->endOfDay();
        $dateLabel = $reportDate->format('d/m/Y');
        $distributionMemo = "Chi lại Pool Share {$dateLabel}";

        return DB::transaction(function () use ($reportDate, $start, $end, $distributionMemo) {
            $snapshot = AdminReportSnapshot::query()->updateOrCreate(
                ['report_date' => $reportDate->toDateString()],
                ['captured_at' => now()],
            );

            $snapshot->logs()->delete();
            $snapshot->poolShareRows()->delete();

            $ordersQuery = PaymentOrder::query()
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$start, $end]);

            $activationCount = (clone $ordersQuery)->count();
            $newPaidMembersCount = (clone $ordersQuery)->distinct('user_id')->count('user_id');
            $grossSalesVnd = (int) (clone $ordersQuery)->sum('amount_vnd');

            $ledgerQuery = LedgerEntry::query()->whereBetween('created_at', [$start, $end]);
            $affiliateCommissionVnd = (int) (clone $ledgerQuery)->where('type', 'referral_commission')->sum('amount_vnd');
            $vatVnd = (int) (clone $ledgerQuery)->where('type', 'company_vat')->sum('amount_vnd');
            $companyRevenueVnd = (int) (clone $ledgerQuery)->where('type', 'company_revenue')->sum('amount_vnd');
            $poolShareInVnd = (int) (clone $ledgerQuery)->where('type', 'payment_shared_pool')->sum('amount_vnd');
            $poolShareDistributedVnd = abs((int) (clone $ledgerQuery)->where('type', 'pool_share_distribution_out')->sum('amount_vnd'));

            $sharedPoolWallet = Wallet::query()
                ->whereNull('owner_type')
                ->whereNull('owner_id')
                ->where('type', 'shared_pool')
                ->first();

            $sharedPoolBalanceVnd = $sharedPoolWallet ? $this->sharedPoolBalanceAt($sharedPoolWallet, $end) : 0;

            [$groupStats, $poolShareRows] = $this->poolShareSnapshot($end, $poolShareInVnd, $distributionMemo);

            $snapshot->update([
                'captured_at' => now(),
                'new_paid_members_count' => $newPaidMembersCount,
                'activation_count' => $activationCount,
                'gross_sales_vnd' => $grossSalesVnd,
                'affiliate_commission_vnd' => max(0, $affiliateCommissionVnd),
                'vat_vnd' => max(0, $vatVnd),
                'company_revenue_vnd' => max(0, $companyRevenueVnd),
                'pool_share_in_vnd' => max(0, $poolShareInVnd),
                'pool_share_distributed_vnd' => max(0, $poolShareDistributedVnd),
                'shared_pool_balance_vnd' => max(0, $sharedPoolBalanceVnd),
                'pool_group_stats' => $groupStats,
            ]);

            if ($poolShareRows->isNotEmpty()) {
                $snapshot->poolShareRows()->createMany($poolShareRows->all());
            }

            $logEntries = LedgerEntry::query()
                ->whereBetween('created_at', [$start, $end])
                ->whereIn('type', [
                    'referral_commission',
                    'company_vat',
                    'company_revenue',
                    'payment_shared_pool',
                    'pool_share_distribution_out',
                ])
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            if ($logEntries->isNotEmpty()) {
                $snapshot->logs()->createMany($logEntries->map(function (LedgerEntry $entry) {
                    return [
                        'ledger_entry_id' => $entry->id,
                        'log_type' => $entry->type,
                        'amount_vnd' => abs((int) $entry->amount_vnd),
                        'memo' => $entry->memo,
                        'occurred_at' => $entry->created_at,
                    ];
                })->all());
            }

            $this->pruneOldSnapshots();

            return $snapshot->fresh(['logs', 'poolShareRows']);
        });
    }

    private function poolShareSnapshot(Carbon $at, int $poolTotal, string $distributionMemo): array
    {
        $eligibleUsers = $this->eligiblePoolShareUsers($at);
        $groupRules = config('quantum.pool_share_groups', []);
        $groupStats = [];
        $rows = collect();

        foreach ($groupRules as $group => $rule) {
            $users = $eligibleUsers->where('group_code', $group)->values();
            $count = $users->count();
            $groupTotal = $count > 0 ? intdiv($poolTotal * (int) $rule['share_bp'], 10000) : 0;
            $amountEach = $count > 0 ? intdiv($groupTotal, $count) : 0;

            $groupStats[$group] = [
                'min' => (int) $rule['min'],
                'max' => $rule['max'] === null ? null : (int) $rule['max'],
                'share_bp' => (int) $rule['share_bp'],
                'qualified_count' => $count,
                'group_total_vnd' => $groupTotal,
                'amount_each_vnd' => $amountEach,
                'distribution_memo' => $distributionMemo,
            ];

            if ($count === 0 || $amountEach <= 0) {
                continue;
            }

            foreach ($users as $user) {
                $rows->push([
                    'user_id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'group_code' => $group,
                    'active_referrals_count' => $user['active_referrals_count'],
                    'payout_vnd' => $amountEach,
                    'account_status' => 'Gói còn hiệu lực',
                    'subscription_ends_at' => $user['subscription_ends_at'],
                ]);
            }
        }

        return [$groupStats, $rows];
    }

    private function eligiblePoolShareUsers(Carbon $at): Collection
    {
        $activeSubscriptions = DB::table('subscriptions')
            ->select('user_id', DB::raw('MAX(ends_at) as subscription_ends_at'))
            ->where('status', 'active')
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>', $at)
            ->groupBy('user_id');

        $activeUsers = User::query()
            ->joinSub($activeSubscriptions, 'active_subscriptions', function ($join) {
                $join->on('active_subscriptions.user_id', '=', 'users.id');
            })
            ->orderBy('users.id')
            ->get([
                'users.id',
                'users.name',
                'users.email',
                'users.trial_started_at',
                'users.created_at',
                DB::raw('active_subscriptions.subscription_ends_at as subscription_ends_at'),
            ]);

        if ($activeUsers->isEmpty()) {
            return collect();
        }

        $usersById = $activeUsers->mapWithKeys(function ($user) use ($at) {
            $base = Carbon::parse($user->trial_started_at ?? $user->created_at);
            $cycleStart = $this->cycleStartFor($base, $at);

            return [
                $user->id => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'subscription_ends_at' => $user->subscription_ends_at ? Carbon::parse($user->subscription_ends_at) : null,
                    'cycle_start' => $cycleStart,
                    'cycle_end' => $cycleStart->copy()->addDays(365),
                    'active_referrals_count' => 0,
                    'group_code' => null,
                ],
            ];
        });

        $activeUserIds = $usersById->keys()->values();

        foreach ($activeUserIds->chunk(2000) as $chunkedIds) {
            $chunkUsers = $usersById->only($chunkedIds->all());
            $minCycleStart = $chunkUsers->min(fn ($item) => $item['cycle_start']->timestamp);
            $minCycleStartAt = Carbon::createFromTimestamp($minCycleStart);

            $referrals = DB::table('referrals')
                ->select('referrer_id', 'activated_at')
                ->whereIn('referrer_id', $chunkedIds->all())
                ->whereNotNull('activated_at')
                ->whereBetween('activated_at', [$minCycleStartAt, $at])
                ->orderBy('activated_at')
                ->get();

            foreach ($referrals as $referral) {
                $userRow = $usersById->get($referral->referrer_id);

                if (! $userRow) {
                    continue;
                }

                $activatedAt = Carbon::parse($referral->activated_at);

                if ($activatedAt->betweenIncluded($userRow['cycle_start'], $userRow['cycle_end'])) {
                    $userRow['active_referrals_count']++;
                    $usersById->put($referral->referrer_id, $userRow);
                }
            }
        }

        return $usersById->map(function (array $row) {
            $row['group_code'] = $this->groupForCount($row['active_referrals_count']);

            return $row;
        })->filter(fn (array $row) => $row['group_code'] !== null)->values();
    }

    private function cycleStartFor(Carbon $base, Carbon $at): Carbon
    {
        if ($base->greaterThan($at)) {
            return $base->copy();
        }

        $cycleIndex = intdiv($base->diffInDays($at), 365);

        return $base->copy()->addDays($cycleIndex * 365);
    }

    private function groupForCount(int $count): ?string
    {
        foreach (config('quantum.pool_share_groups', []) as $group => $rule) {
            $min = (int) $rule['min'];
            $max = $rule['max'];

            if ($count >= $min && ($max === null || $count <= (int) $max)) {
                return (string) $group;
            }
        }

        return null;
    }

    private function sharedPoolBalanceAt(Wallet $sharedPoolWallet, Carbon $end): int
    {
        return (int) LedgerEntry::query()
            ->where('wallet_id', $sharedPoolWallet->id)
            ->where('created_at', '<=', $end)
            ->sum('amount_vnd');
    }

    private function pruneOldSnapshots(): void
    {
        $idsToDelete = AdminReportSnapshot::query()
            ->orderByDesc('report_date')
            ->pluck('id')
            ->slice(10)
            ->values();

        if ($idsToDelete->isNotEmpty()) {
            AdminReportSnapshot::query()->whereIn('id', $idsToDelete)->delete();
        }
    }
}
