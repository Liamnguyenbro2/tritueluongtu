<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->query('period', 'all');
        $user = $request->user();

        $baseQuery = Referral::query()
            ->with(['referred.subscriptions'])
            ->where('referrer_id', $user->id);
        $showFullContacts = $user->is_admin;

        $filteredQuery = clone $baseQuery;
        $this->applyPeriod($filteredQuery, $period);

        $referrals = $filteredQuery
            ->latest()
            ->get()
            ->map(function (Referral $referral) use ($showFullContacts) {
                $referred = $referral->referred;
                $activeSubscription = $referred?->subscriptions
                    ->where('status', 'active')
                    ->where('ends_at', '>', now())
                    ->sortByDesc('ends_at')
                    ->first();

                return [
                    'referral' => $referral,
                    'user' => $referred,
                    'display_email' => $this->maskEmail($referred?->email, $showFullContacts),
                    'display_phone' => $this->maskPhone($referred?->phone, $showFullContacts),
                    'is_active' => $referral->activated_at !== null,
                    'subscription_ends_at' => $activeSubscription?->ends_at,
                ];
            });

        $allReferrals = $baseQuery->get();
        $filteredTotal = $referrals->count();
        $filteredActive = $referrals->where('is_active', true)->count();

        $weeks = collect(range(4, 0))->map(function (int $index) use ($user) {
            $start = now()->startOfWeek()->subWeeks($index);
            $end = $start->copy()->endOfWeek();

            return [
                'label' => $start->format('d/m'),
                'invited' => Referral::query()
                    ->where('referrer_id', $user->id)
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
                'activated' => Referral::query()
                    ->where('referrer_id', $user->id)
                    ->whereBetween('activated_at', [$start, $end])
                    ->count(),
            ];
        });

        $maxChartValue = max(1, $weeks->max(fn ($week) => max($week['invited'], $week['activated'])));

        return view('affiliate.index', [
            'period' => $period,
            'referrals' => $referrals,
            'totalInvited' => $allReferrals->count(),
            'totalActivated' => $allReferrals->whereNotNull('activated_at')->count(),
            'filteredTotal' => $filteredTotal,
            'filteredActive' => $filteredActive,
            'weeks' => $weeks,
            'maxChartValue' => $maxChartValue,
        ]);
    }

    private function applyPeriod($query, string $period): void
    {
        $start = match ($period) {
            'day' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            default => null,
        };

        if ($start) {
            $query->where('created_at', '>=', $start);
        }
    }

    private function maskEmail(?string $email, bool $showFull): string
    {
        if (! $email) {
            return 'Không xác định';
        }

        if ($showFull || ! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);

        if (strlen($local) <= 2) {
            return substr($local, 0, 1).'***@'.$domain;
        }

        $prefix = substr($local, 0, min(3, strlen($local)));
        $suffix = substr($local, -1);

        return $prefix.'***'.$suffix.'@'.$domain;
    }

    private function maskPhone(?string $phone, bool $showFull): string
    {
        if (! $phone) {
            return 'Không xác định';
        }

        if ($showFull) {
            return $phone;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) <= 5) {
            return $digits;
        }

        return substr($digits, 0, 3).str_repeat('*', max(1, strlen($digits) - 5)).substr($digits, -2);
    }
}
