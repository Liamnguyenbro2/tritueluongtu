<?php

namespace App\Http\Controllers;

use App\Models\AccountantAuditLog;
use App\Models\LedgerEntry;
use App\Models\PaymentOrder;
use App\Models\Plan;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\AccountantAuditLogService;
use App\Services\SimplePdfExporter;
use App\Services\SimpleXlsxExporter;
use App\Services\TransactionLogService;
use App\Services\WalletLedgerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AccountantController extends Controller
{
    public function dashboard(): View
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $monthStart = now()->startOfMonth();
        $prevMonthStart = now()->copy()->subMonthNoOverflow()->startOfMonth();
        $prevMonthEnd = now()->copy()->subMonthNoOverflow()->endOfMonth();

        $todayRevenue = $this->paidOrdersQuery($todayStart, $todayEnd)->sum('amount_vnd');
        $monthRevenue = $this->paidOrdersQuery($monthStart, now())->sum('amount_vnd');
        $prevMonthRevenue = $this->paidOrdersQuery($prevMonthStart, $prevMonthEnd)->sum('amount_vnd');

        $todayTopup = $this->transactionTotals($todayStart, $todayEnd, [TransactionLog::TYPE_MONEY_IN]);
        $monthTopup = $this->transactionTotals($monthStart, now(), [TransactionLog::TYPE_MONEY_IN]);
        $todayWithdraw = abs($this->transactionTotals($todayStart, $todayEnd, [TransactionLog::TYPE_MONEY_OUT], referencePrefix: 'WD-'));
        $monthWithdraw = abs($this->transactionTotals($monthStart, now(), [TransactionLog::TYPE_MONEY_OUT], referencePrefix: 'WD-'));
        $todayNetProfit = $this->companyRevenueTotal($todayStart, $todayEnd);

        $successCount = $this->transactionsBaseQuery()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('status', TransactionLog::STATUS_SUCCESS)
            ->count();
        $failedCount = $this->transactionsBaseQuery()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('status', TransactionLog::STATUS_FAILED)
            ->count();
        $pendingCount = $this->transactionsBaseQuery()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('status', TransactionLog::STATUS_PENDING)
            ->count();

        $growthPercent = $prevMonthRevenue > 0
            ? round((($monthRevenue - $prevMonthRevenue) / $prevMonthRevenue) * 100, 1)
            : ($monthRevenue > 0 ? 100.0 : 0.0);

        $dailyRevenue = collect(range(6, 0))->map(function (int $offset) {
            $day = now()->copy()->subDays($offset);
            $start = $day->copy()->startOfDay();
            $end = $day->copy()->endOfDay();

            return [
                'label' => $day->format('d/m'),
                'revenue' => $this->paidOrdersQuery($start, $end)->sum('amount_vnd'),
                'topup' => $this->transactionTotals($start, $end, [TransactionLog::TYPE_MONEY_IN]),
                'withdraw' => abs($this->transactionTotals($start, $end, [TransactionLog::TYPE_MONEY_OUT], referencePrefix: 'WD-')),
            ];
        });

        $topCustomers = TransactionLog::query()
            ->selectRaw('user_id, COUNT(*) as transaction_count, SUM(ABS(amount)) as total_amount')
            ->with('user:id,name,email')
            ->where('status', TransactionLog::STATUS_SUCCESS)
            ->groupBy('user_id')
            ->orderByDesc('transaction_count')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        $latestAudits = AccountantAuditLog::query()
            ->with('actor:id,name,email')
            ->latest()
            ->limit(10)
            ->get();

        return view('accountant.dashboard', [
            'todayRevenue' => $todayRevenue,
            'todayTopup' => $todayTopup,
            'todayWithdraw' => $todayWithdraw,
            'todayNetProfit' => $todayNetProfit,
            'successCount' => $successCount,
            'failedCount' => $failedCount,
            'pendingCount' => $pendingCount,
            'monthRevenue' => $monthRevenue,
            'monthTopup' => $monthTopup,
            'monthWithdraw' => $monthWithdraw,
            'growthPercent' => $growthPercent,
            'dailyRevenue' => $dailyRevenue,
            'topCustomers' => $topCustomers,
            'latestAudits' => $latestAudits,
            'maxRevenue' => max(1, (int) $dailyRevenue->max('revenue')),
            'maxCashflow' => max(1, (int) $dailyRevenue->flatMap(fn (array $row) => [$row['topup'], $row['withdraw']])->max()),
        ]);
    }

    public function transactions(Request $request): View
    {
        $transactions = $this->filteredTransactions($request)
            ->paginate(20)
            ->withQueryString();

        return view('accountant.transactions.index', [
            'transactions' => $transactions,
            'typeOptions' => $this->accountantTransactionTypeOptions(),
            'statusOptions' => TransactionLog::statusOptions(),
            'filters' => $request->only(['date_from', 'date_to', 'user', 'type', 'status', 'q']),
        ]);
    }

    public function exportTransactions(Request $request, string $format, SimpleXlsxExporter $xlsx, SimplePdfExporter $pdf): Response
    {
        $rows = $this->filteredTransactions($request)
            ->limit(5000)
            ->get()
            ->map(fn (TransactionLog $log) => [
                'STT' => $log->id,
                'Ma GD' => $log->reference_id,
                'User' => $log->user?->email,
                'Loai' => $log->typeLabel(),
                'So tien' => $log->amount,
                'Noi dung' => $log->description,
                'Ghi chu' => $log->notes,
                'Thoi gian' => $log->created_at->format('d/m/Y H:i:s'),
                'Trang thai' => $log->statusLabel(),
            ])
            ->values()
            ->all();

        return $this->exportRows($format, 'accountant-transactions-'.now()->format('Y-m-d'), 'Transactions', $rows, $xlsx, $pdf);
    }

    public function transactionShow(TransactionLog $transaction): View
    {
        $transaction->load('user');

        return view('accountant.transactions.show', compact('transaction'));
    }

    public function transactionInvoice(TransactionLog $transaction): View
    {
        $transaction->load('user');

        return view('accountant.transactions.invoice', compact('transaction'));
    }

    public function withdrawals(Request $request): View
    {
        $withdrawals = $this->withdrawalsQuery($request)
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->filled('user'), function (Builder $query) use ($request) {
                $term = trim((string) $request->string('user'));
                $query->whereHas('user', fn (Builder $userQuery) => $userQuery
                    ->where('email', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%"));
            })
            ->paginate(20)
            ->withQueryString();

        return view('accountant.withdrawals.index', [
            'withdrawals' => $withdrawals,
            'auditLogByWithdrawal' => AccountantAuditLog::query()
                ->where('target_type', (new WithdrawalRequest())->getMorphClass())
                ->whereIn('target_id', $withdrawals->pluck('id'))
                ->with('actor:id,name,email')
                ->latest()
                ->get()
                ->groupBy('target_id'),
            'filters' => $request->only(['status', 'user']),
            'exportDate' => $this->resolvedWithdrawalExportDate($request)->format('Y-m-d'),
            'exportDateMin' => now()->subDays(6)->format('Y-m-d'),
            'exportDateMax' => now()->format('Y-m-d'),
        ]);
    }

    public function exportWithdrawals(Request $request, SimpleXlsxExporter $xlsx, AccountantAuditLogService $auditLogs): BinaryFileResponse
    {
        $exportDate = $this->resolvedWithdrawalExportDate($request);
        $start = $exportDate->copy()->startOfDay();
        $end = $exportDate->copy()->endOfDay();

        $withdrawals = WithdrawalRequest::query()
            ->with([
                'user:id,username,email',
                'user.kycVerification:user_id,full_name,citizen_id,address',
                'bankAccount:id,user_id,bank_name,account_number',
            ])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('withdrawal_number')
            ->orderBy('id')
            ->get();
        $rows = $withdrawals
            ->map(fn (WithdrawalRequest $withdrawal) => $this->withdrawalExportRowUtf8($withdrawal))
            ->values()
            ->all();

        $auditLogs->record(
            $request->user(),
            'withdrawal.export.xlsx',
            'Da xuat Excel yeu cau rut tien',
            null,
            'ID tai khoan: '.($request->user()->username ?? $request->user()->email)
            .PHP_EOL.'Ngay du lieu: '.$exportDate->format('d/m/Y')
            .PHP_EOL.'So ban ghi: '.$withdrawals->count()
            .PHP_EOL.'Thoi gian thuc hien: '.now()->format('d/m/Y H:i:s')
        );

        $filename = 'withdrawals_'.$exportDate->format('d-m-Y').'.xlsx';

        return response()
            ->download($xlsx->build('Withdrawals', $rows), $filename)
            ->deleteFileAfterSend();
    }

    public function approveWithdrawal(Request $request, WithdrawalRequest $withdrawal, WalletLedgerService $wallets, TransactionLogService $transactionLogs, AccountantAuditLogService $auditLogs): RedirectResponse
    {
        DB::transaction(function () use ($request, $withdrawal, $wallets, $transactionLogs, $auditLogs) {
            if ($withdrawal->status !== 'pending') {
                return;
            }

            if (! $this->hasWithdrawalLedger($withdrawal, 'withdrawal_hold')) {
                $wallet = $wallets->walletForUser($withdrawal->user()->firstOrFail());
                $wallets->debit($wallet, (int) $withdrawal->amount_vnd, 'withdrawal_completed', $withdrawal, 'Da hoan tat viec rut tien');
            } else {
                $this->withdrawalLedger($withdrawal, 'withdrawal_hold')?->update([
                    'type' => 'withdrawal_completed',
                    'memo' => 'Da hoan tat viec rut tien',
                ]);
            }

            $withdrawal->update([
                'status' => 'approved',
                'decided_at' => now(),
            ]);

            $transactionLogs->markWithdrawalSuccessful($withdrawal);
            $auditLogs->record($request->user(), 'withdrawal.approve', 'Duyet yeu cau rut tien '.$this->money($withdrawal->amount_vnd), $withdrawal);
        });

        return back()->with('status', 'Da duyet yeu cau rut tien.');
    }

    public function rejectWithdrawal(Request $request, WithdrawalRequest $withdrawal, WalletLedgerService $wallets, TransactionLogService $transactionLogs, AccountantAuditLogService $auditLogs): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $withdrawal, $wallets, $transactionLogs, $auditLogs, $data) {
            if ($withdrawal->status !== 'pending') {
                return;
            }

            if ($this->hasWithdrawalLedger($withdrawal, 'withdrawal_hold') && ! $this->hasWithdrawalLedger($withdrawal, 'withdrawal_refund')) {
                $wallet = $wallets->walletForUser($withdrawal->user()->firstOrFail());
                $wallets->credit($wallet, (int) $withdrawal->amount_vnd, 'withdrawal_refund', $withdrawal, 'Hoan tien yeu cau rut bi tu choi: '.$data['note']);
            }

            $withdrawal->update([
                'status' => 'rejected',
                'admin_note' => $data['note'],
                'decided_at' => now(),
            ]);

            $transactionLogs->markWithdrawalFailed($withdrawal, $data['note']);
            $auditLogs->record($request->user(), 'withdrawal.reject', 'Tu choi yeu cau rut tien '.$this->money($withdrawal->amount_vnd), $withdrawal, $data['note']);
        });

        return back()->with('status', 'Da tu choi yeu cau rut tien.');
    }

    public function markTransferred(Request $request, WithdrawalRequest $withdrawal, AccountantAuditLogService $auditLogs): RedirectResponse
    {
        if (! in_array($withdrawal->status, ['approved', 'transferred'], true)) {
            return back()->withErrors(['status' => 'Chi co the danh dau chuyen khoan sau khi da duyet.']);
        }

        $withdrawal->update([
            'status' => 'transferred',
            'decided_at' => $withdrawal->decided_at ?? now(),
        ]);

        $auditLogs->record($request->user(), 'withdrawal.transferred', 'Danh dau da chuyen khoan '.$this->money($withdrawal->amount_vnd), $withdrawal);

        return back()->with('status', 'Da danh dau chuyen khoan.');
    }

    public function resendTransfer(Request $request, WithdrawalRequest $withdrawal, AccountantAuditLogService $auditLogs): RedirectResponse
    {
        $auditLogs->record($request->user(), 'withdrawal.resend', 'Gui lai lenh chuyen khoan '.$this->money($withdrawal->amount_vnd), $withdrawal, 'Yeu cau gui lai lenh chuyen khoan.');

        return back()->with('status', 'Da ghi nhan yeu cau gui lai lenh chuyen.');
    }

    public function deposits(Request $request): View
    {
        $filters = $this->validatedDepositFilters($request);
        $orders = $this->filteredDepositsQuery($filters)
            ->paginate(20)
            ->withQueryString();

        return view('accountant.deposits.index', [
            'orders' => $orders,
            'filters' => [
                'user' => $filters['user'],
                'status' => $filters['status'],
                'from_date' => $filters['from_date'],
                'to_date' => $filters['to_date'],
            ],
            'exportUrl' => route('accountant.deposits.export', array_filter([
                'user' => $filters['user'],
                'status' => $filters['status'],
                'from_date' => $filters['from_date'],
                'to_date' => $filters['to_date'],
            ], fn ($value) => $value !== null && $value !== '')),
            'dateMin' => now()->subDays(29)->format('Y-m-d'),
            'dateMax' => now()->format('Y-m-d'),
        ]);
    }

    public function exportDeposits(Request $request, SimpleXlsxExporter $xlsx, AccountantAuditLogService $auditLogs): BinaryFileResponse
    {
        $filters = $this->validatedDepositFilters($request);
        $orders = $this->filteredDepositsQuery($filters)->get();
        $rows = $orders
            ->values()
            ->map(fn (PaymentOrder $order, int $index) => $this->depositExportRowUtf8($order, $index + 1))
            ->all();

        $auditLogs->record(
            $request->user(),
            'deposits.export.xlsx',
            'Da xuat Excel giao dich nap tien',
            null,
            'ID tai khoan: '.($request->user()->username ?? $request->user()->email)
            .PHP_EOL.'Bo loc user: '.($filters['user'] !== '' ? $filters['user'] : 'Tat ca')
            .PHP_EOL.'Bo loc trang thai: '.($filters['status'] !== '' ? $filters['status'] : 'Tat ca')
            .PHP_EOL.'Tu ngay: '.($filters['from_date'] ?? 'Khong chon')
            .PHP_EOL.'Den ngay: '.($filters['to_date'] ?? 'Khong chon')
            .PHP_EOL.'So ban ghi: '.$orders->count()
            .PHP_EOL.'Thoi gian thuc hien: '.now()->format('d/m/Y H:i:s')
        );

        return response()
            ->download(
                $xlsx->build('Deposits', $rows),
                $this->depositExportFilename($filters['from_date'], $filters['to_date'])
            )
            ->deleteFileAfterSend();
    }

    public function wallets(Request $request): View
    {
        $term = trim((string) $request->string('q'));
        $users = User::query()
            ->with('wallet')
            ->where('role', 'user')
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $nested) => $nested
                ->where('email', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('username', 'like', "%{$term}%")))
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $walletStats = TransactionLog::query()
            ->selectRaw("
                user_id,
                SUM(CASE WHEN transaction_type = 'money_in' AND status = 'success' THEN amount ELSE 0 END) as deposited_total,
                SUM(CASE WHEN transaction_type = 'money_out' AND status = 'success' AND reference_id like 'WD-%' THEN ABS(amount) ELSE 0 END) as withdrawn_total,
                SUM(CASE WHEN transaction_type in ('plan_upgrade','plan_renewal') AND status = 'success' THEN ABS(amount) ELSE 0 END) as spent_total
            ")
            ->whereIn('user_id', $users->pluck('id'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return view('accountant.wallets.index', [
            'users' => $users,
            'walletStats' => $walletStats,
            'search' => $term,
        ]);
    }

    public function adjustWallet(Request $request, User $user, WalletLedgerService $wallets, AccountantAuditLogService $auditLogs): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:add,subtract'],
            'amount_vnd' => ['required', 'integer', 'min:1000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        abort_if($user->isAdmin() || $user->isAccountant(), 403);

        $wallet = $wallets->walletForUser($user);

        try {
            if ($data['direction'] === 'add') {
                $wallets->credit($wallet, (int) $data['amount_vnd'], 'accountant_adjustment_in', null, $data['note'] ?: 'Ke toan cong tien vao vi');
            } else {
                $wallets->debit($wallet, (int) $data['amount_vnd'], 'accountant_adjustment_out', null, $data['note'] ?: 'Ke toan tru tien khoi vi');
            }
        } catch (RuntimeException) {
            throw ValidationException::withMessages([
                'amount_vnd' => 'So du vi hien tai khong du de tru tien.',
            ]);
        }

        $action = $data['direction'] === 'add' ? 'wallet.adjust.add' : 'wallet.adjust.subtract';
        $verb = $data['direction'] === 'add' ? 'Cong vi' : 'Tru vi';
        $auditLogs->record($request->user(), $action, "{$verb} {$this->money((int) $data['amount_vnd'])} cho {$user->email}", $user, $data['note'] ?? null);

        return back()->with('status', 'Da cap nhat so du khach hang.');
    }

    public function toggleWalletLock(Request $request, User $user, WalletLedgerService $wallets, AccountantAuditLogService $auditLogs): RedirectResponse
    {
        abort_if($user->isAdmin() || $user->isAccountant(), 403);

        $wallet = $wallets->walletForUser($user);
        $wallet->update(['is_locked' => ! $wallet->is_locked]);

        $auditLogs->record(
            $request->user(),
            $wallet->is_locked ? 'wallet.lock' : 'wallet.unlock',
            ($wallet->is_locked ? 'Khoa vi' : 'Mo vi').' cho '.$user->email,
            $wallet
        );

        return back()->with('status', $wallet->is_locked ? 'Da khoa vi khach hang.' : 'Da mo vi khach hang.');
    }

    public function revenue(): View
    {
        $planRevenue = Plan::query()
            ->withSum(['paymentOrders as revenue_vnd' => fn (Builder $query) => $query->where('status', 'paid')], 'amount_vnd')
            ->withCount(['paymentOrders as order_count' => fn (Builder $query) => $query->where('status', 'paid')])
            ->orderBy('price_vnd')
            ->get();

        return view('accountant.revenue', [
            'planRevenue' => $planRevenue,
            'totalRevenue' => (int) $planRevenue->sum('revenue_vnd'),
        ]);
    }

    public function reports(Request $request): View
    {
        $period = (string) $request->string('period', 'month');
        [$start, $end, $label] = $this->resolvePeriod($period);

        $revenue = $this->paidOrdersQuery($start, $end)->sum('amount_vnd');
        $topup = $this->transactionTotals($start, $end, [TransactionLog::TYPE_MONEY_IN]);
        $withdraw = abs($this->transactionTotals($start, $end, [TransactionLog::TYPE_MONEY_OUT], referencePrefix: 'WD-'));
        $profit = $this->companyRevenueTotal($start, $end);

        $topDepositors = TransactionLog::query()
            ->selectRaw('user_id, SUM(amount) as total_amount')
            ->with('user:id,name,email')
            ->where('transaction_type', TransactionLog::TYPE_MONEY_IN)
            ->where('status', TransactionLog::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('user_id')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        $topWithdrawers = TransactionLog::query()
            ->selectRaw('user_id, SUM(ABS(amount)) as total_amount')
            ->with('user:id,name,email')
            ->where('transaction_type', TransactionLog::TYPE_MONEY_OUT)
            ->where('status', TransactionLog::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('user_id')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        $topServiceUsers = TransactionLog::query()
            ->selectRaw('user_id, COUNT(*) as usage_count')
            ->with('user:id,name,email')
            ->whereIn('transaction_type', [TransactionLog::TYPE_PLAN_UPGRADE, TransactionLog::TYPE_PLAN_RENEWAL])
            ->where('status', TransactionLog::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('user_id')
            ->orderByDesc('usage_count')
            ->limit(5)
            ->get();

        return view('accountant.reports', compact(
            'period',
            'label',
            'revenue',
            'topup',
            'withdraw',
            'profit',
            'topDepositors',
            'topWithdrawers',
            'topServiceUsers'
        ));
    }

    public function exportReports(Request $request, string $format, SimpleXlsxExporter $xlsx, SimplePdfExporter $pdf): Response
    {
        $period = (string) $request->string('period', 'month');
        [$start, $end, $label] = $this->resolvePeriod($period);

        $rows = [
            ['Metric', 'Value'],
            ['Bao cao', $label],
            ['Doanh thu', $this->paidOrdersQuery($start, $end)->sum('amount_vnd')],
            ['Tong nap', $this->transactionTotals($start, $end, [TransactionLog::TYPE_MONEY_IN])],
            ['Tong rut', abs($this->transactionTotals($start, $end, [TransactionLog::TYPE_MONEY_OUT], referencePrefix: 'WD-'))],
            ['Loi nhuan thuan', $this->companyRevenueTotal($start, $end)],
        ];

        return $this->exportRows($format, 'accountant-report-'.now()->format('Y-m-d'), 'Reports', $rows, $xlsx, $pdf);
    }

    public function auditLogs(): View
    {
        $logs = AccountantAuditLog::query()
            ->with('actor:id,name,email')
            ->latest()
            ->paginate(30);

        return view('accountant.audit-logs', compact('logs'));
    }

    private function validatedDepositFilters(Request $request): array
    {
        $data = $request->validate([
            'user' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:pending,paid,failed'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $fromDate = $data['from_date'] ?? null;
        $toDate = $data['to_date'] ?? null;

        if ($fromDate !== null && $toDate === null) {
            $toDate = $fromDate;
        }

        if ($toDate !== null && $fromDate === null) {
            $fromDate = $toDate;
        }

        $from = $fromDate ? Carbon::createFromFormat('Y-m-d', $fromDate)->startOfDay() : null;
        $to = $toDate ? Carbon::createFromFormat('Y-m-d', $toDate)->endOfDay() : null;
        $today = now()->endOfDay();
        $minDate = now()->subDays(29)->startOfDay();

        if ($from !== null && $to !== null && $from->gt($to)) {
            throw ValidationException::withMessages([
                'from_date' => html_entity_decode('Ng&#224;y b&#7855;t &#273;&#7847;u kh&#244;ng &#273;&#432;&#7907;c l&#7899;n h&#417;n ng&#224;y k&#7871;t th&#250;c.'),
            ]);
        }

        if (
            ($from !== null && ($from->lt($minDate) || $from->gt($today)))
            || ($to !== null && ($to->lt($minDate) || $to->gt($today)))
            || ($from !== null && $to !== null && $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) > 29)
        ) {
            throw ValidationException::withMessages([
                'from_date' => html_entity_decode('Ch&#7881; &#273;&#432;&#7907;c l&#7885;c d&#7919; li&#7879;u t&#7889;i &#273;a 30 ng&#224;y g&#7847;n nh&#7845;t.'),
            ]);
        }

        return [
            'user' => trim((string) ($data['user'] ?? '')),
            'status' => (string) ($data['status'] ?? ''),
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'from' => $from,
            'to' => $to,
        ];
    }

    private function filteredDepositsQuery(array $filters): Builder
    {
        return PaymentOrder::query()
            ->with([
                'user:id,username,name,email',
                'user.kycVerification:user_id,full_name,citizen_id,address',
                'plan:id,name',
            ])
            ->when($filters['status'] !== '', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['user'] !== '', function (Builder $query) use ($filters) {
                $term = $filters['user'];
                $query->whereHas('user', fn (Builder $userQuery) => $userQuery
                    ->where('email', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%"));
            })
            ->when($filters['from'] !== null && $filters['to'] !== null, function (Builder $query) use ($filters) {
                $from = $filters['from'];
                $to = $filters['to'];

                $query->where(function (Builder $nested) use ($from, $to) {
                    $nested->where(function (Builder $dated) use ($from, $to) {
                        $dated->whereNotNull('paid_at')->whereBetween('paid_at', [$from, $to]);
                    })->orWhere(function (Builder $fallback) use ($from, $to) {
                        $fallback->whereNull('paid_at')->whereBetween('created_at', [$from, $to]);
                    });
                });
            })
            ->latest();
    }

    private function depositChannelLabel(PaymentOrder $order): string
    {
        return match ($order->metadata['payment_method'] ?? 'bank_qr') {
            'wallet' => html_entity_decode('V&#237; s&#7889; d&#432;'),
            '9pay' => '9Pay',
            'vnpay' => 'VNPay',
            default => 'QR Banking',
        };
    }

    private function depositBankLabel(PaymentOrder $order): string
    {
        return $order->metadata['bank_code']
            ?? $order->metadata['bank_name']
            ?? html_entity_decode('Ch&#432;a c&#7853;p nh&#7853;t');
    }

    private function depositStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => html_entity_decode('&#272;ang x&#7917; l&#253;'),
            'paid' => html_entity_decode('Th&#224;nh c&#244;ng'),
            'failed' => html_entity_decode('Th&#7845;t b&#7841;i'),
            default => html_entity_decode('Kh&#225;c'),
        };
    }

    private function depositExportFilename(?string $fromDate, ?string $toDate): string
    {
        if ($fromDate !== null && $toDate !== null) {
            $from = Carbon::createFromFormat('Y-m-d', $fromDate)->format('Ymd');
            $to = Carbon::createFromFormat('Y-m-d', $toDate)->format('Ymd');

            return $from === $to
                ? "deposits_{$from}.xlsx"
                : "deposits_{$from}_{$to}.xlsx";
        }

        return 'deposits_'.now()->format('Ymd').'.xlsx';
    }

    private function depositExportRowUtf8(PaymentOrder $order, int $index): array
    {
        $kyc = $order->user?->kycVerification;
        $missing = html_entity_decode('Ch&#432;a c&#7853;p nh&#7853;t');

        return [
            'STT' => $index,
            html_entity_decode('M&#227; giao d&#7883;ch') => $order->code,
            html_entity_decode('M&#227; &#273;&#7889;i so&#225;t') => $order->provider_transaction_id ?: '-',
            html_entity_decode('ID T&#224;i kho&#7843;n') => $order->user?->username ?? $missing,
            html_entity_decode('S&#7889; CCCD') => $kyc?->citizen_id ?? $missing,
            html_entity_decode('H&#7885; t&#234;n CCCD') => $kyc?->full_name ?? $missing,
            html_entity_decode('&#272;&#7883;a ch&#7881; CCCD') => $kyc?->address ?? $missing,
            html_entity_decode('G&#243;i mua') => $order->plan?->name ?? $missing,
            html_entity_decode('S&#7889; ti&#7873;n') => number_format((int) $order->amount_vnd, 0, ',', '.').html_entity_decode('&#273;'),
            html_entity_decode('Ng&#226;n h&#224;ng / k&#234;nh') => $this->depositChannelLabel($order).' / '.$this->depositBankLabel($order),
            html_entity_decode('Th&#7901;i gian') => optional($order->paid_at ?? $order->created_at)?->format('d/m/Y H:i:s') ?? '-',
            html_entity_decode('Tr&#7841;ng th&#225;i') => $this->depositStatusLabel($order->status),
        ];
    }

    private function filteredTransactions(Request $request): Builder
    {
        return TransactionLog::query()
            ->with('user:id,name,email')
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->string('date_to')))
            ->when($request->filled('type') && $request->string('type') !== 'all', fn (Builder $query) => $query->where('transaction_type', $request->string('type')))
            ->when($request->filled('status') && $request->string('status') !== 'all', fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->filled('user'), function (Builder $query) use ($request) {
                $term = trim((string) $request->string('user'));
                $query->whereHas('user', fn (Builder $userQuery) => $userQuery
                    ->where('email', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%"));
            })
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $term = trim((string) $request->string('q'));
                $query->where(function (Builder $nested) use ($term) {
                    $nested->where('description', 'like', "%{$term}%")
                        ->orWhere('notes', 'like', "%{$term}%")
                        ->orWhere('reference_id', 'like', "%{$term}%");
                });
            })
            ->latest();
    }

    private function transactionsBaseQuery(): Builder
    {
        return TransactionLog::query();
    }

    private function transactionTotals(Carbon $start, Carbon $end, array $types, ?string $status = TransactionLog::STATUS_SUCCESS, ?string $referencePrefix = null): int
    {
        return (int) TransactionLog::query()
            ->whereIn('transaction_type', $types)
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->when($referencePrefix !== null, fn (Builder $query) => $query->where('reference_id', 'like', $referencePrefix.'%'))
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
    }

    private function paidOrdersQuery(Carbon $start, Carbon $end): Builder
    {
        return PaymentOrder::query()
            ->where('status', 'paid')
            ->where(function (Builder $query) use ($start, $end) {
                $query->whereBetween('paid_at', [$start, $end])
                    ->orWhere(function (Builder $fallback) use ($start, $end) {
                        $fallback->whereNull('paid_at')->whereBetween('created_at', [$start, $end]);
                    });
            });
    }

    private function companyRevenueTotal(Carbon $start, Carbon $end): int
    {
        return (int) DB::table('ledger_entries')
            ->where('type', 'company_revenue')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount_vnd');
    }

    private function resolvePeriod(string $period): array
    {
        return match ($period) {
            'day' => [now()->startOfDay(), now()->endOfDay(), 'Theo ngay'],
            'week' => [now()->startOfWeek(), now()->endOfWeek(), 'Theo tuan'],
            'year' => [now()->startOfYear(), now()->endOfYear(), 'Theo nam'],
            default => [now()->startOfMonth(), now()->endOfMonth(), 'Theo thang'],
        };
    }

    private function exportRows(string $format, string $filenameBase, string $sheet, array $rows, SimpleXlsxExporter $xlsx, SimplePdfExporter $pdf): Response
    {
        return match ($format) {
            'xlsx' => response()->download($xlsx->build($sheet, $rows), $filenameBase.'.xlsx')->deleteFileAfterSend(),
            'csv' => response($this->rowsToCsv($rows), 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filenameBase.'.csv"',
            ]),
            'pdf' => response()->download(
                $pdf->build($sheet, collect($rows)->map(fn (array $row) => implode(' | ', array_map(fn ($value) => (string) $value, $row)))->all()),
                $filenameBase.'.pdf'
            )->deleteFileAfterSend(),
            default => abort(404),
        };
    }

    private function rowsToCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    private function accountantTransactionTypeOptions(): array
    {
        return [
            'all' => 'Tat ca',
            TransactionLog::TYPE_MONEY_IN => 'Nap tien',
            TransactionLog::TYPE_MONEY_OUT => 'Rut tien',
            TransactionLog::TYPE_PLAN_UPGRADE => 'Thanh toan dich vu',
            TransactionLog::TYPE_PLAN_RENEWAL => 'Gia han dich vu',
            TransactionLog::TYPE_REFUND => 'Hoan tien',
            TransactionLog::TYPE_OTHER => 'Dieu chinh so du',
            TransactionLog::TYPE_AFFILIATE => 'Affiliate',
            TransactionLog::TYPE_POOL_SHARE => 'Pool Share',
        ];
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

    private function money(int $amount): string
    {
        return number_format($amount, 0, ',', '.').'d';
    }

    private function withdrawalsQuery(Request $request): Builder
    {
        return WithdrawalRequest::query()
            ->with([
                'user:id,username,name,email',
                'user.kycVerification:user_id,full_name,citizen_id,address',
                'bankAccount:id,user_id,bank_name,account_number',
            ])
            ->orderByDesc('withdrawal_number')
            ->orderByDesc('id');
    }

    private function withdrawalExportRow(WithdrawalRequest $withdrawal): array
    {
        return [
            'STT' => (int) $withdrawal->withdrawal_number,
            'ID TÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â i khoÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂºÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â£n' => $withdrawal->user?->username ?? '-',
            'SÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¹Ã…â€œ CCCD' => $withdrawal->user?->kycVerification?->citizen_id ?? '-',
            'HÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â tÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âªn CCCD' => $withdrawal->user?->kycVerification?->full_name ?? '-',
            'NgÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢n hÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â ng' => $withdrawal->bankAccount?->bank_name ?? '-',
            'STK NgÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢n hÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â ng' => $withdrawal->bankAccount?->account_number ?? '-',
            'ThÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âi gian' => $withdrawal->created_at?->format('d/m/Y H:i:s') ?? '-',
            'TrÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂºÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ng thÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡i' => match ($withdrawal->status) {
                'pending' => 'ChÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â duyÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¡t',
                'approved' => 'ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âang xÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­ lÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â½',
                'transferred' => 'HoÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â n thÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â nh',
                'rejected' => 'TÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â« chÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¹Ã…â€œi',
                default => 'KhÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡c',
            },
        ];
    }

    private function withdrawalExportRowUtf8(WithdrawalRequest $withdrawal): array
    {
        return [
            'STT' => (int) $withdrawal->withdrawal_number,
            html_entity_decode('ID T&#224;i kho&#7843;n') => $withdrawal->user?->username ?? '-',
            html_entity_decode('S&#7889; CCCD') => $withdrawal->user?->kycVerification?->citizen_id ?? '-',
            html_entity_decode('H&#7885; t&#234;n CCCD') => $withdrawal->user?->kycVerification?->full_name ?? '-',
            html_entity_decode('Ng&#226;n h&#224;ng') => $withdrawal->bankAccount?->bank_name ?? '-',
            html_entity_decode('STK Ng&#226;n h&#224;ng') => $withdrawal->bankAccount?->account_number ?? '-',
            html_entity_decode('S&#7889; ti&#7873;n r&#250;t') => number_format((int) $withdrawal->amount_vnd, 0, ',', '.').'Ä‘',
            html_entity_decode('Th&#7901;i gian') => $withdrawal->created_at?->format('d/m/Y H:i:s') ?? '-',
            html_entity_decode('Tr&#7841;ng th&#225;i') => match ($withdrawal->status) {
                'pending' => html_entity_decode('Ch&#7901; duy&#7879;t'),
                'approved' => html_entity_decode('&#272;ang x&#7917; l&#253;'),
                'transferred' => html_entity_decode('Ho&#224;n th&#224;nh'),
                'rejected' => html_entity_decode('T&#7915; ch&#7889;i'),
                default => html_entity_decode('Kh&#225;c'),
            },
        ];
    }

    private function resolvedWithdrawalExportDate(Request $request): Carbon
    {
        $data = $request->validate([
            'export_date' => [
                'nullable',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $date = Carbon::createFromFormat('Y-m-d', (string) $value)->startOfDay();
                    $today = now()->startOfDay();
                    $minDate = now()->subDays(6)->startOfDay();

                    if ($date->lt($minDate) || $date->gt($today)) {
                        $fail('ChÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â° ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¹Ã…â€œÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â°ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â£c xuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂºÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¥t dÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¯ liÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¡u trong vÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â²ng 7 ngÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â y gÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂºÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â§n nhÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂºÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¥t.');
                    }
                },
            ],
        ]);

        $value = $data['export_date'] ?? now()->format('Y-m-d');

        return Carbon::createFromFormat('Y-m-d', $value);
    }

    private function withdrawalStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'ChÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â duyÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¡t',
            'approved' => 'ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âang xÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­ lÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â½',
            'transferred' => 'HoÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â n thÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â nh',
            'rejected' => 'TÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â« chÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¹Ã…â€œi',
            default => 'KhÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡c',
        };
    }
}


