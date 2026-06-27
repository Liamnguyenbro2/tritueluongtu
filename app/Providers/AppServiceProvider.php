<?php

namespace App\Providers;

use App\Models\AdminNotification;
use App\Models\Referral;
use App\Models\SiteSetting;
use App\Services\WalletLedgerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $user = request()->user();
            $notifications = collect();
            $readAt = null;
            $view->with('brandSettings', SiteSetting::branding());

            if ($user) {
                $readAt = session('notifications_read_at_'.$user->id);
                $readAt = $readAt ? Carbon::parse($readAt) : null;

                $notifications = $notifications
                    ->merge(AdminNotification::query()
                        ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
                        ->latest()
                        ->limit(5)
                        ->get()
                        ->map(fn (AdminNotification $notification) => [
                            'type' => 'system',
                            'icon' => 'bell',
                            'title' => $notification->title,
                            'body' => $notification->body,
                            'time' => $notification->created_at,
                            'tone' => 'violet',
                        ]));

                $notifications = $notifications
                    ->merge(Referral::query()
                        ->with('referred')
                        ->where('referrer_id', $user->id)
                        ->latest()
                        ->limit(5)
                        ->get()
                        ->map(fn (Referral $referral) => [
                            'type' => 'referral',
                            'icon' => 'user-plus',
                            'title' => 'Referral đăng ký mới',
                            'body' => ($referral->referred?->name ?? 'Thành viên mới').' đã đăng ký qua link của bạn.',
                            'time' => $referral->created_at,
                            'tone' => 'fuchsia',
                        ]));

                $wallet = app(WalletLedgerService::class)->walletForUser($user);
                $notifications = $notifications
                    ->merge($wallet->ledgerEntries()
                        ->latest()
                        ->limit(8)
                        ->get()
                        ->map(fn ($entry) => [
                            'type' => 'wallet',
                            'icon' => $entry->amount_vnd >= 0 ? 'trending-up' : 'trending-down',
                            'title' => 'Biến động số dư',
                            'body' => ($entry->amount_vnd >= 0 ? '+' : '').number_format($entry->amount_vnd, 0, ',', '.').' đ'.($entry->memo ? ' - '.$entry->memoWithTimestamp() : ''),
                            'time' => $entry->created_at,
                            'tone' => $entry->amount_vnd >= 0 ? 'emerald' : 'rose',
                        ]));

                $activeSuspension = $user->activeSuspension()->first();
                if ($activeSuspension) {
                    $notifications->push([
                        'type' => 'account',
                        'icon' => 'shield-alert',
                        'title' => 'Trạng thái tài khoản',
                        'body' => 'Tài khoản đang bị giới hạn: '.$activeSuspension->reason,
                        'time' => $activeSuspension->created_at,
                        'tone' => 'rose',
                    ]);
                }

                $notifications = $notifications
                    ->sortByDesc('time')
                    ->values();
            }

            $unreadCount = $readAt
                ? $notifications->filter(fn ($item) => $item['time']->gt($readAt))->count()
                : $notifications->count();

            $view->with('headerNotifications', $notifications->take(5)->values());
            $view->with('headerUnreadNotificationCount', min(99, $unreadCount));
        });
    }
}
