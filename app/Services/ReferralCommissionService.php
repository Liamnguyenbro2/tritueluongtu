<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class ReferralCommissionService
{
    public function activeReferralCount(User $user, ?Carbon $at = null): int
    {
        $at ??= now();
        [$cycleStart, $cycleEnd] = $this->currentCycleWindow($user, $at);

        return $user->referralsMade()
            ->whereNotNull('activated_at')
            ->whereBetween('activated_at', [$cycleStart, $cycleEnd])
            ->count();
    }

    public function currentPercent(User $user): int
    {
        return (int) config('quantum.affiliate_commission_percent', 30);
    }

    public function poolShareGroup(User $user, ?Carbon $at = null): ?string
    {
        $count = $this->activeReferralCount($user, $at);

        foreach (config('quantum.pool_share_groups', []) as $group => $rule) {
            $min = (int) $rule['min'];
            $max = $rule['max'];

            if ($count >= $min && ($max === null || $count <= (int) $max)) {
                return (string) $group;
            }
        }

        return null;
    }

    public function currentCycleWindow(User $user, ?Carbon $at = null): array
    {
        $at ??= now();
        $base = ($user->trial_started_at ?? $user->created_at)->copy();

        if ($base->greaterThan($at)) {
            return [$base, $base->copy()->addDays(365)];
        }

        $cycleIndex = intdiv($base->diffInDays($at), 365);
        $cycleStart = $base->copy()->addDays($cycleIndex * 365);

        return [$cycleStart, $cycleStart->copy()->addDays(365)];
    }
}
