<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\WithdrawalRequest;
use App\Services\WalletLedgerService;
use App\Support\SupportedBanks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request, WalletLedgerService $wallets): View
    {
        $wallet = $wallets->walletForUser($request->user())->load('ledgerEntries');
        $rawLedgerEntries = $wallet->ledgerEntries()
            ->with('reference')
            ->latest()
            ->get();
        $heldWithdrawalIds = $rawLedgerEntries
            ->where('reference_type', (new WithdrawalRequest)->getMorphClass())
            ->whereIn('type', ['withdrawal_hold', 'withdrawal_completed'])
            ->pluck('reference_id')
            ->filter()
            ->all();
        $filteredLedgerEntries = $rawLedgerEntries
            ->reject(fn ($entry) => $entry->type === 'withdrawal_payout' && in_array($entry->reference_id, $heldWithdrawalIds, true))
            ->values();
        $ledgerPage = LengthAwarePaginator::resolveCurrentPage('ledger_page');
        $ledgerEntries = new LengthAwarePaginator(
            $filteredLedgerEntries->forPage($ledgerPage, 15)->values(),
            $filteredLedgerEntries->count(),
            15,
            $ledgerPage,
            [
                'path' => route('wallet'),
                'pageName' => 'ledger_page',
            ]
        );
        $ledgerEntries->withQueryString();

        return view('wallet.index', [
            'wallet' => $wallet,
            'bankAccount' => $bankAccount = BankAccount::query()->where('user_id', $request->user()->id)->first(),
            'bankOptions' => SupportedBanks::options(),
            'selectedBankName' => old('bank_name', SupportedBanks::normalize($bankAccount?->bank_name) ?? $bankAccount?->bank_name),
            'ledgerEntries' => $ledgerEntries,
            'withdrawals' => WithdrawalRequest::query()->where('user_id', $request->user()->id)->latest()->get(),
            'withdrawalPitPercent' => (int) config('quantum.withdrawal_personal_income_tax_percent', 10),
        ]);
    }

    public function saveBankAccount(Request $request): RedirectResponse
    {
        $user = $request->user();
        $existing = BankAccount::query()->where('user_id', $user->id)->first();

        $data = $request->validate([
            'bank_name' => SupportedBanks::validationRules(),
            'account_number' => ['required', 'string', 'max:50'],
            'account_holder' => ['required', 'string', 'max:100'],
        ], [
            'bank_name.required' => 'Vui lòng chọn ngân hàng.',
            'bank_name.in' => 'Tên ngân hàng không hợp lệ.',
        ]);

        $data['bank_name'] = SupportedBanks::normalize($data['bank_name']) ?? $data['bank_name'];

        if ($existing && ! $existing->can_edit) {
            abort(403, 'Số tài khoản chỉ được nhập một lần.');
        }

        BankAccount::query()->updateOrCreate(
            ['user_id' => $user->id],
            $data + ['can_edit' => false]
        );

        return back()->with('status', 'Đã lưu thông tin ngân hàng.');
    }

    public function withdraw(Request $request, WalletLedgerService $wallets): RedirectResponse
    {
        $minimumAmount = (int) config('quantum.withdrawal_min_vnd', 100000);
        $request->merge([
            'amount_vnd' => preg_replace('/\D+/', '', (string) $request->input('amount_vnd')),
        ]);

        $data = $request->validate([
            'amount_vnd' => ['required', 'integer', 'min:'.$minimumAmount],
            'bank_account_id' => [
                'required',
                Rule::exists('bank_accounts', 'id')
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
        ], [
            'amount_vnd.required' => 'Vui lòng nhập số tiền cần rút.',
            'amount_vnd.integer' => 'Số tiền rút không hợp lệ.',
            'amount_vnd.min' => 'Số tiền rút tối thiểu là 100.000 đ.',
            'bank_account_id.required' => 'Vui lòng chọn tài khoản ngân hàng.',
            'bank_account_id.exists' => 'Tài khoản ngân hàng không hợp lệ.',
        ]);

        $wallet = $wallets->walletForUser($request->user());

        if ($wallet->is_locked) {
            throw ValidationException::withMessages([
                'amount_vnd' => 'Ví của bạn đang bị khóa tạm thời.',
            ]);
        }

        if ($wallet->balance_vnd < (int) $data['amount_vnd']) {
            throw ValidationException::withMessages([
                'amount_vnd' => 'Số tiền rút không được vượt quá số dư ví hiện có.',
            ]);
        }

        $grossAmount = (int) $data['amount_vnd'];
        $pitRatePercent = (int) config('quantum.withdrawal_personal_income_tax_percent', 10);
        $pitAmount = intdiv($grossAmount * $pitRatePercent, 100);
        $netAmount = $grossAmount - $pitAmount;

        DB::transaction(function () use ($request, $data, $wallet, $wallets, $grossAmount, $pitRatePercent, $pitAmount, $netAmount) {
            $nextWithdrawalNumber = ((int) WithdrawalRequest::query()->max('withdrawal_number')) + 1;

            $withdrawal = WithdrawalRequest::query()->create([
                'withdrawal_number' => $nextWithdrawalNumber,
                'user_id' => $request->user()->id,
                'bank_account_id' => $data['bank_account_id'],
                'amount_vnd' => $grossAmount,
                'pit_rate_percent' => $pitRatePercent,
                'pit_amount_vnd' => $pitAmount,
                'net_amount_vnd' => $netAmount,
                'status' => 'pending',
            ]);

            $wallets->debit($wallet, $grossAmount, 'withdrawal_hold', $withdrawal, 'Tạm giữ yêu cầu rút tiền');
        });

        return back()->with('status', 'Đã tạo yêu cầu rút tiền.');
    }
}
