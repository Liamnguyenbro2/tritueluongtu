<?php

namespace App\Http\Controllers;

use App\Models\AccountSuspension;
use App\Models\LedgerEntry;
use App\Models\PaymentOrder;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\SiteSetting;
use App\Models\TransactionLog;
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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class AdminController extends Controller
{
    private const RESERVED_USERNAMES = [
        'admin',
        'administrator',
        'support',
        'root',
        'system',
        'mod',
        'moderator',
        'staff',
        'api',
        'login',
        'register',
        'dashboard',
    ];

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
            ->paginate(10)
            ->withQueryString();

        return view('admin.index', [
            'users' => $users,
            'search' => $search,
            'accountants' => User::query()
                ->where('role', 'accountant')
                ->orderBy('name')
                ->limit(12)
                ->get(),
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

    public function users(): View
    {
        $users = User::query()
            ->where('is_admin', false)
            ->where('role', 'user')
            ->with([
                'wallet',
                'subscriptions' => function ($query) {
                    $query->with('plan')
                        ->where('status', 'active')
                        ->where('starts_at', '<=', now())
                        ->where('ends_at', '>', now())
                        ->orderByDesc('ends_at');
                },
            ])
            ->latest()
            ->paginate(10);

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function userShow(User $user, WalletLedgerService $wallets): View
    {
        abort_if($user->isAdmin() || $user->isAccountant(), 404);

        $user->load([
            'subscriptions' => function ($query) {
                $query->with('plan')
                    ->where('status', 'active')
                    ->where('starts_at', '<=', now())
                    ->where('ends_at', '>', now())
                    ->orderByDesc('ends_at');
            },
        ]);

        $transactions = TransactionLog::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10, ['*'], 'transactions_page')
            ->withQueryString();

        return view('admin.users.show', [
            'user' => $user,
            'wallet' => $wallets->walletForUser($user),
            'transactions' => $transactions,
            'activePlanLabel' => $this->activePlanLabel($user),
        ]);
    }

    public function storeAccountant(Request $request, WalletLedgerService $wallets): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:users,username'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'username.required' => 'Vui lòng nhập username cho kế toán.',
            'username.unique' => 'Username này đã tồn tại.',
            'name.required' => 'Vui lòng nhập họ tên kế toán.',
            'email.required' => 'Vui lòng nhập email kế toán.',
            'email.email' => 'Email kế toán không hợp lệ.',
            'email.unique' => 'Email này đã tồn tại.',
            'phone.required' => 'Vui lòng nhập số điện thoại kế toán.',
            'phone.unique' => 'Số điện thoại này đã tồn tại.',
            'password.required' => 'Vui lòng nhập mật khẩu cho kế toán.',
            'password.min' => 'Mật khẩu kế toán phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu kế toán chưa khớp.',
        ]);

        $accountant = DB::transaction(function () use ($data, $wallets) {
            $user = User::query()->create([
                'username' => $data['username'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'is_admin' => false,
                'role' => 'accountant',
            ]);

            $user->profile()->updateOrCreate([], [
                'accepted_terms' => true,
                'accepted_terms_at' => now(),
            ]);

            ReferralLink::query()->updateOrCreate(
                ['user_id' => $user->id],
                ['code' => $this->generateUniqueReferralCode($user->username)]
            );

            $wallets->walletForUser($user);

            return $user;
        });

        return redirect()
            ->route('admin.index')
            ->with('status', "Đã tạo tài khoản kế toán {$accountant->email}.");
    }

    public function sharedPoolHistory(WalletLedgerService $wallets): View
    {
        $sharedPool = $wallets->systemWallet('shared_pool');
        $poolEntries = $sharedPool->ledgerEntries()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $payoutsByDate = LedgerEntry::query()
            ->join('wallets', 'wallets.id', '=', 'ledger_entries.wallet_id')
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'wallets.owner_id')
                    ->where('wallets.owner_type', '=', User::class);
            })
            ->where('wallets.type', 'user')
            ->where('ledger_entries.type', 'pool_share_payout')
            ->orderBy('ledger_entries.created_at')
            ->orderBy('ledger_entries.id')
            ->get([
                'ledger_entries.id',
                'ledger_entries.amount_vnd',
                'ledger_entries.memo',
                'ledger_entries.created_at',
                'users.id as user_id',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->groupBy(fn ($entry) => Carbon::parse($entry->created_at)->toDateString());

        $dateKeys = $poolEntries->map(fn (LedgerEntry $entry) => $entry->created_at->toDateString())
            ->merge($payoutsByDate->keys())
            ->unique()
            ->sort()
            ->values();

        $runningBalance = 0;

        $historyDays = $dateKeys->map(function (string $dateKey) use ($poolEntries, $payoutsByDate, &$runningBalance) {
            $entriesForDay = $poolEntries->filter(fn (LedgerEntry $entry) => $entry->created_at->toDateString() === $dateKey);
            $openingBalance = $runningBalance;
            $poolInVnd = (int) $entriesForDay
                ->where('type', 'payment_shared_pool')
                ->sum('amount_vnd');
            $distributedVnd = abs((int) $entriesForDay
                ->where('type', 'pool_share_distribution_out')
                ->sum('amount_vnd'));
            $runningBalance += (int) $entriesForDay->sum('amount_vnd');

            $payouts = $payoutsByDate->get($dateKey, collect())
                ->map(fn ($entry) => (object) [
                    'user_id' => $entry->user_id,
                    'user_name' => $entry->user_name,
                    'user_email' => $entry->user_email,
                    'amount_vnd' => (int) $entry->amount_vnd,
                    'memo' => $entry->memo,
                    'created_at' => Carbon::parse($entry->created_at),
                ])
                ->values();

            return (object) [
                'date' => Carbon::parse($dateKey),
                'opening_balance_vnd' => $openingBalance,
                'pool_in_vnd' => $poolInVnd,
                'distributed_vnd' => $distributedVnd,
                'closing_balance_vnd' => $runningBalance,
                'payout_count' => $payouts->count(),
                'payouts' => $payouts,
                'status' => match (true) {
                    $distributedVnd > 0 => 'distributed',
                    $poolInVnd > 0 => 'pending',
                    default => 'idle',
                },
            ];
        })->sortByDesc(fn ($day) => $day->date->timestamp)->values();

        $lastDistributionEntry = $poolEntries
            ->where('type', 'pool_share_distribution_out')
            ->sortByDesc('created_at')
            ->first();

        return view('admin.shared-pool', [
            'sharedPool' => $sharedPool,
            'historyDays' => $historyDays,
            'totalPoolInVnd' => (int) $poolEntries->where('type', 'payment_shared_pool')->sum('amount_vnd'),
            'totalDistributedVnd' => abs((int) $poolEntries->where('type', 'pool_share_distribution_out')->sum('amount_vnd')),
            'distributedDays' => $historyDays->where('distributed_vnd', '>', 0)->count(),
            'lastDistributionAt' => $lastDistributionEntry?->created_at,
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

    public function updateBasicInfo(Request $request, User $user): RedirectResponse
    {
        $normalizedUsername = $this->normalizeUsername((string) $request->input('username'));
        $normalizedEmail = Str::lower(trim((string) $request->input('email')));

        $request->merge([
            'username' => $normalizedUsername,
            'name' => trim((string) $request->input('name')),
            'email' => $normalizedEmail,
            'phone' => trim((string) $request->input('phone')),
        ]);

        $data = $request->validate([
            'username' => [
                'required',
                'string',
                'min:4',
                'max:30',
                'regex:/^[a-z0-9._]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (in_array((string) $value, self::RESERVED_USERNAMES, true)) {
                        $fail('ID tài khoản này không được phép sử dụng.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'regex:/^\d{10}$/', Rule::unique('users', 'phone')->ignore($user->id)],
        ], [
            'username.required' => 'Vui lòng nhập ID tài khoản.',
            'username.min' => 'ID tài khoản phải có ít nhất 4 ký tự.',
            'username.max' => 'ID tài khoản tối đa 30 ký tự.',
            'username.regex' => 'ID tài khoản chỉ được dùng chữ, số, dấu chấm hoặc dấu gạch dưới.',
            'username.unique' => 'ID tài khoản đã được sử dụng.',
            'name.required' => 'Vui lòng nhập họ và tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại phải gồm đúng 10 chữ số.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
        ]);

        $user->update($data);

        return back()->with('status', 'Đã cập nhật thông tin cơ bản của user.');
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

    private function activePlanLabel(User $user): string
    {
        $activeSubscription = $user->subscriptions->first();
        $planCode = $activeSubscription?->plan?->code;

        return match ($planCode) {
            'monthly' => 'Gói tháng',
            'yearly' => 'Gói năm',
            default => 'Chưa kích hoạt',
        };
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

    public function approveWithdrawal(WithdrawalRequest $withdrawal, WalletLedgerService $wallets, \App\Services\TransactionLogService $transactionLogs): RedirectResponse
    {
        DB::transaction(function () use ($withdrawal, $wallets, $transactionLogs) {
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
            $transactionLogs->markWithdrawalSuccessful($withdrawal);
        });

        return back()->with('status', 'Đã duyệt yêu cầu rút tiền.');
    }

    public function rejectWithdrawal(Request $request, WithdrawalRequest $withdrawal, WalletLedgerService $wallets, \App\Services\TransactionLogService $transactionLogs): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($withdrawal, $wallets, $data, $transactionLogs) {
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

            $transactionLogs->markWithdrawalFailed($withdrawal, $data['admin_note']);
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

    private function generateUniqueReferralCode(string $username): string
    {
        $base = strtoupper(preg_replace('/[^A-Z0-9]/', '', $username)) ?: 'ACCOUNTANT';
        $base = substr($base, 0, 16);
        $candidate = $base;
        $suffix = 1;

        while (ReferralLink::query()->where('code', $candidate)->exists()) {
            $candidate = substr($base, 0, max(1, 16 - strlen((string) $suffix))).$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function normalizeUsername(string $username): string
    {
        return Str::lower(trim($username));
    }
}
