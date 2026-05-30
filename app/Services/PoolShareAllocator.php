<?php

namespace App\Services;

use Illuminate\Support\Collection;
use RuntimeException;

class PoolShareAllocator
{
    public function allocate(int $poolTotal, Collection $recipientsByGroup): array
    {
        $rules = collect(config('quantum.pool_share_groups', []));
        $this->guardPercentTotal($rules);

        $summary = [];
        $payouts = collect();

        if ($poolTotal <= 0) {
            foreach ($rules as $group => $rule) {
                $summary[$group] = $this->emptySummary($rule);
            }

            return [
                'summary' => $summary,
                'payouts' => $payouts,
                'paid_total' => 0,
            ];
        }

        $normalizedGroups = $rules->map(function (array $rule, string $group) use ($recipientsByGroup) {
            $recipients = collect($recipientsByGroup->get($group, collect()))->values();

            return [
                'group' => $group,
                'rule' => $rule,
                'recipients' => $recipients,
                'count' => $recipients->count(),
            ];
        });

        $eligibleGroups = $normalizedGroups->filter(fn (array $group) => $group['count'] > 0)->values();

        if ($eligibleGroups->isEmpty()) {
            foreach ($rules as $group => $rule) {
                $summary[$group] = $this->emptySummary($rule);
            }

            return [
                'summary' => $summary,
                'payouts' => $payouts,
                'paid_total' => 0,
            ];
        }

        $effectiveBasisPoints = (int) $eligibleGroups->sum(fn (array $group) => (int) $group['rule']['share_bp']);
        $distributedGroupTotal = 0;
        $lastEligibleIndex = $eligibleGroups->count() - 1;

        foreach ($normalizedGroups as $groupData) {
            $group = $groupData['group'];
            $rule = $groupData['rule'];
            $recipients = $groupData['recipients'];
            $count = $groupData['count'];

            if ($count === 0) {
                $summary[$group] = $this->emptySummary($rule);
                continue;
            }

            $baseGroupTotal = intdiv($poolTotal * (int) $rule['share_bp'], $effectiveBasisPoints);

            $currentEligibleIndex = $eligibleGroups->search(fn (array $eligible) => $eligible['group'] === $group);
            $groupTotal = $currentEligibleIndex === $lastEligibleIndex
                ? $poolTotal - $distributedGroupTotal
                : $baseGroupTotal;

            $distributedGroupTotal += $groupTotal;

            $amountEach = intdiv($groupTotal, $count);
            $groupRemainder = $groupTotal - ($amountEach * $count);

            foreach ($recipients->values() as $recipientIndex => $recipient) {
                $amount = $amountEach;

                if ($recipientIndex === $count - 1) {
                    $amount += $groupRemainder;
                }

                $payouts->push([
                    'group' => $group,
                    'recipient' => $recipient,
                    'amount_vnd' => $amount,
                ]);
            }

            $summary[$group] = [
                'users' => $count,
                'amount_each' => $amountEach,
                'paid_total' => $groupTotal,
                'share_bp' => (int) $rule['share_bp'],
                'min' => (int) $rule['min'],
                'max' => $rule['max'] === null ? null : (int) $rule['max'],
            ];
        }

        return [
            'summary' => $summary,
            'payouts' => $payouts,
            'paid_total' => (int) $payouts->sum('amount_vnd'),
        ];
    }

    private function emptySummary(array $rule): array
    {
        return [
            'users' => 0,
            'amount_each' => 0,
            'paid_total' => 0,
            'share_bp' => (int) $rule['share_bp'],
            'min' => (int) $rule['min'],
            'max' => $rule['max'] === null ? null : (int) $rule['max'],
        ];
    }

    private function guardPercentTotal(Collection $rules): void
    {
        $total = (int) $rules->sum(fn (array $rule) => (int) $rule['share_bp']);

        if ($total !== 10000) {
            throw new RuntimeException('Pool share group percentages must equal 10000 basis points.');
        }
    }
}
