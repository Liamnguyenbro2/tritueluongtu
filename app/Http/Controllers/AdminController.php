<?php

namespace App\Http\Controllers;

use App\Models\AccountSuspension;
use App\Models\LedgerEntry;
use App\Models\PaymentOrder;
use App\Models\Referral;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use Carbon\Carbon;
use App\Services\ReferralCommissionService;
use App\Services\WalletLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->limit(50)
            ->get();

        return view('admin.index', [
            'users' => $users,
            'search' => $search,
            'orders' => PaymentOrder::query()->latest()->limit(20)->get(),
            'withdrawals' => WithdrawalRequest::query()->with('user')->latest()->limit(20)->get(),
            'systemWallets' => Wallet::query()->whereNull('owner_type')->whereNull('owner_id')->get(),
            'brandSettings' => SiteSetting::branding(),
            'adminWallet' => app(WalletLedgerService::class)->walletForUser($request->user()),
            'transferLogs' => $request->user()->wallet?->ledgerEntries()
                ->where('type', 'admin_transfer_out')
                ->latest()
                ->limit(10)
                ->get() ?? collect(),
        ]); 
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'brand_logo_url' => ['nullable', 'string', 'max:2048'],
            'brand_logo_file' => ['nullable', 'image', 'max:4096'],
            'brand_eyebrow' => ['required', 'string', 'max:40'],
            'brand_name' => ['required', 'string', 'max:60'],
        ]);

        $logoUrl = $data['brand_logo_url'] ?? null;

        if ($request->hasFile('brand_logo_file')) {
            $path = $request->file('brand_logo_file')->store('brand-logos', 'public');
            $logoUrl = Storage::disk('public')->url($path);
        }

        SiteSetting::setValue('brand_logo_url', $logoUrl);
        SiteSetting::setValue('brand_eyebrow', $data['brand_eyebrow']);
        SiteSetting::setValue('brand_name', $data['brand_name']);

        return back()->with('status', 'Đã cập nhật logo và text thương hiệu.');
    }

    public function passwords(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $digits = preg_replace('/\D+/', '', $search);
        $users = collect();

        if ($search !== '') {
            $users = User::query()
                ->where(function ($query) use ($search, $digits) {
                    $query
                        ->where('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");

                    if ($digits !== '' && $digits !== $search) {
                        $query->orWhere('phone', 'like', "%{$digits}%");
                    }
                })
                ->orderBy('id')
                ->limit(10)
                ->get();
        }

        return view('admin.passwords', [
            'search' => $search,
            'users' => $users,
        ]);
    }

    public function updateUserPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'user_id.required' => 'Vui lòng chọn user cần đổi mật khẩu.',
            'user_id.exists' => 'User không tồn tại.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới phải có tối thiểu 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu mới chưa khớp.',
        ]);

        $user = User::query()->findOrFail($data['user_id']);
        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return redirect()
            ->route('admin.passwords', ['q' => $user->email])
            ->with('status', "Đã đổi mật khẩu cho {$user->email}.");
    }

    public function transferToUser(Request $request, WalletLedgerService $wallets): RedirectResponse
    {
        $request->merge([
            'amount_vnd' => preg_replace('/\D+/', '', (string) $request->input('amount_vnd')),
        ]);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'amount_vnd' => ['required', 'integer', 'min:1000'],
        ]);

        $admin = $request->user();
        $recipient = User::query()->findOrFail($data['user_id']);
        $amount = (int) $data['amount_vnd'];

        if ($recipient->id === $admin->id) {
            throw ValidationException::withMessages([
                'user_id' => 'Admin không thể chuyển cho chính mình.',
            ]);
        }

        try {
            DB::transaction(function () use ($wallets, $admin, $recipient, $amount) {
                $adminWallet = $wallets->walletForUser($admin);
                $recipientWallet = $wallets->walletForUser($recipient);
                $memo = "Admin chuyển ví cho {$recipient->email}";

                $wallets->debit($adminWallet, $amount, 'admin_transfer_out', null, $memo);
                $wallets->credit($recipientWallet, $amount, 'admin_transfer_in', null, "Nhận chuyển ví từ admin {$admin->email}");
            });
        } catch (RuntimeException) {
            throw ValidationException::withMessages([
                'amount_vnd' => 'Số tiền chuyển phải nhỏ hơn hoặc bằng số dư ví admin hiện có.',
            ]);
        }

        return back()->with('status', 'Đã chuyển số dư ví cho user.');
    }

    public function report(Request $request, User $user, ReferralCommissionService $referrals, WalletLedgerService $wallets): View
    {
        $period = $request->query('period', 'all');
        $wallet = $wallets->walletForUser($user);
        $referralQuery = Referral::query()
            ->with(['referred.subscriptions'])
            ->where('referrer_id', $user->id);

        $filteredReferralQuery = clone $referralQuery;
        $this->applyPeriod($filteredReferralQuery, $period);

        $filteredTotal = (clone $filteredReferralQuery)->count();
        $filteredActive = (clone $filteredReferralQuery)->whereNotNull('activated_at')->count();
        $referralRows = $filteredReferralQuery
            ->latest()
            ->paginate(15, ['*'], 'report_members_page')
            ->withQueryString();
        $referralRows->getCollection()->transform(function (Referral $referral) {
                $referred = $referral->referred;
                $activeSubscription = $referred?->subscriptions
                    ->where('status', 'active')
                    ->where('ends_at', '>', now())
                    ->sortByDesc('ends_at')
                    ->first();

                return [
                    'referral' => $referral,
                    'user' => $referred,
                    'is_active' => $referral->activated_at !== null,
                    'subscription_ends_at' => $activeSubscription?->ends_at,
                ];
            });

        $allReferrals = $referralQuery->get();
        $affiliateIncomeVnd = (int) $wallet->ledgerEntries()
            ->where('type', 'referral_commission')
            ->where('amount_vnd', '>', 0)
            ->sum('amount_vnd');
        $sharedPoolIncomeVnd = (int) $wallet->ledgerEntries()
            ->whereIn('type', ['pool_share_payout', 'shared_pool_income', 'shared_pool_payout', 'shared_pool_bonus', 'pool_distribution', 'pool_bonus', 'payment_shared_pool'])
            ->where('amount_vnd', '>', 0)
            ->sum('amount_vnd');
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

        $ordersByMonth = PaymentOrder::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->groupBy(fn (PaymentOrder $order) => $order->created_at->format('Y-m'))
            ->map(fn ($orders, $month) => (object) [
                'month' => $month,
                'total' => $orders->sum('amount_vnd'),
            ])
            ->sortByDesc('month')
            ->take(3)
            ->values();

        return view('admin.report', [
            'user' => $user,
            'wallet' => $wallet,
            'activeSuspension' => $user->activeSuspension()->first(),
            'referralCount' => $referrals->activeReferralCount($user),
            'referralPercent' => $referrals->currentPercent($user),
            'ordersByMonth' => $ordersByMonth,
            'period' => $period,
            'referralRows' => $referralRows,
            'totalInvited' => $allReferrals->count(),
            'totalActivated' => $allReferrals->whereNotNull('activated_at')->count(),
            'affiliateIncomeVnd' => $affiliateIncomeVnd,
            'sharedPoolIncomeVnd' => $sharedPoolIncomeVnd,
            'filteredTotal' => $filteredTotal,
            'filteredActive' => $filteredActive,
            'weeks' => $weeks,
            'maxChartValue' => max(1, $weeks->max(fn ($week) => max($week['invited'], $week['activated']))),
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

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:temporary,permanent'],
            'reason' => ['required', 'string'],
            'ends_at' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($user, $data) {
            AccountSuspension::query()
                ->where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->where('starts_at', '<=', now())
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->update(['revoked_at' => now()]);

            AccountSuspension::query()->create([
                'user_id' => $user->id,
                'type' => $data['type'],
                'reason' => $data['reason'],
                'starts_at' => now(),
                'ends_at' => $data['type'] === 'temporary' ? ($data['ends_at'] ?? now()->addDays(7)) : null,
            ]);
        });

        return back()->with('status', 'Đã khóa tài khoản.');
    }

    public function unlock(User $user): RedirectResponse
    {
        $count = AccountSuspension::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->update(['revoked_at' => now()]);

        return back()->with('status', $count > 0
            ? 'Đã mở khóa tài khoản user.'
            : 'Tài khoản không có khóa đang hoạt động.'
        );
    }

    public function unlockBank(User $user): RedirectResponse
    {
        $user->bankAccount?->update(['can_edit' => true]);

        return back()->with('status', 'Đã mở quyền sửa tài khoản ngân hàng.');
    }

    public function approveWithdrawal(WithdrawalRequest $withdrawal, WalletLedgerService $wallets): RedirectResponse
    {
        DB::transaction(function () use ($withdrawal, $wallets) {
            if ($withdrawal->status !== 'pending') {
                return;
            }

            if (! $this->hasWithdrawalLedger($withdrawal, 'withdrawal_hold')) {
                $wallet = $wallets->walletForUser($withdrawal->user()->firstOrFail());
                $wallets->debit($wallet, (int) $withdrawal->amount_vnd, 'withdrawal_completed', $withdrawal, 'Đã hoàn tất việc rút tiền');
            } else {
                $this->withdrawalLedger($withdrawal, 'withdrawal_hold')?->update([
                    'type' => 'withdrawal_completed',
                    'memo' => 'Đã hoàn tất việc rút tiền',
                ]);
            }

            $withdrawal->update(['status' => 'approved', 'decided_at' => now()]);
        });

        return back()->with('status', 'Đã duyệt yêu cầu rút tiền.');
    }

    public function rejectWithdrawal(Request $request, WithdrawalRequest $withdrawal, WalletLedgerService $wallets): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($withdrawal, $wallets, $data) {
            if ($withdrawal->status !== 'pending') {
                return;
            }

            if (
                $this->hasWithdrawalLedger($withdrawal, 'withdrawal_hold')
                && ! $this->hasWithdrawalLedger($withdrawal, 'withdrawal_refund')
            ) {
                $wallet = $wallets->walletForUser($withdrawal->user()->firstOrFail());
                $wallets->credit($wallet, (int) $withdrawal->amount_vnd, 'withdrawal_refund', $withdrawal, 'Hoàn tiền yêu cầu rút bị từ chối: '.$data['admin_note']);
            }

            $withdrawal->update([
                'status' => 'rejected',
                'admin_note' => $data['admin_note'],
                'decided_at' => now(),
            ]);
        });

        return back()->with('status', 'Đã từ chối yêu cầu rút tiền.');
    }

    private function hasWithdrawalLedger(WithdrawalRequest $withdrawal, string $type): bool
    {
        return $this->withdrawalLedger($withdrawal, $type) !== null;
    }

    private function withdrawalLedger(WithdrawalRequest $withdrawal, string $type): ?LedgerEntry
    {
        return LedgerEntry::query()
            ->where('reference_type', $withdrawal->getMorphClass())
            ->where('reference_id', $withdrawal->id)
            ->where('type', $type)
            ->first();
    }
}
