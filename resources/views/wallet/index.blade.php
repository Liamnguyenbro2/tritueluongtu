@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="grid gap-5 lg:grid-cols-[.9fr_1.1fr]">
        <div class="relative overflow-hidden rounded-[32px] border border-white/10 bg-gradient-to-br from-violet-500/25 to-amber-300/10 p-6 shadow-glow backdrop-blur-2xl sm:p-8">
            <div class="absolute -right-16 -top-16 h-52 w-52 rounded-full bg-violet-500/30 blur-3xl"></div>
            <div class="relative">
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-100/80">Balance wallet</p>
                <h1 class="mt-3 text-4xl font-black">Ví số dư</h1>
                <p class="mt-6 text-5xl font-black">{{ number_format($wallet->balance_vnd, 0, ',', '.') }} đ</p>
                <p class="mt-4 text-sm text-slate-300">Mọi biến động số dư được ghi nhận bằng ledger entry.</p>
            </div>
        </div>

        <div
            class="glass rounded-[32px] p-6"
            x-data="{ confirmBank: false, bankName: @js($selectedBankName), accountNumber: @js(old('account_number', $bankAccount?->account_number)), accountHolder: @js(old('account_holder', $bankAccount?->account_holder)) }"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black">Tài khoản ngân hàng</h2>
                    @if($bankAccount && ! $bankAccount->can_edit)
                        <p class="mt-2 text-sm font-semibold text-amber-100">Bạn không được phép thay đổi thông tin tài khoản ngân hàng.</p>
                    @endif
                </div>
                <div class="rounded-full px-3 py-1 text-xs font-bold {{ $bankAccount && ! $bankAccount->can_edit ? 'bg-amber-300/10 text-amber-100' : 'bg-emerald-400/10 text-emerald-100' }}">
                    {{ $bankAccount && ! $bankAccount->can_edit ? 'Đã khóa chỉnh sửa' : 'Có thể cập nhật' }}
                </div>
            </div>

            <form x-ref="bankForm" method="post" action="{{ route('wallet.bank-account') }}" class="mt-5 grid gap-4 sm:grid-cols-3" @submit.prevent="confirmBank = true">
                @csrf
                <select class="premium-input appearance-none" name="bank_name" x-model="bankName" required @disabled($bankAccount && ! $bankAccount->can_edit)>
                    <option value="">{!! html_entity_decode('-- Ch&#7885;n ng&#226;n h&#224;ng --') !!}</option>
                    @foreach($bankOptions as $bankOption)
                        <option value="{{ $bankOption }}">{{ $bankOption }}</option>
                    @endforeach
                </select>
                <input class="premium-input" name="account_number" placeholder="Số tài khoản" x-model="accountNumber" value="{{ old('account_number', $bankAccount?->account_number) }}" required @disabled($bankAccount && ! $bankAccount->can_edit)>
                <input class="premium-input" name="account_holder" placeholder="Chủ tài khoản" x-model="accountHolder" value="{{ old('account_holder', $bankAccount?->account_holder) }}" required @disabled($bankAccount && ! $bankAccount->can_edit)>
                <div class="sm:col-span-3">
                    @if($bankAccount && ! $bankAccount->can_edit)
                        <p class="text-sm text-slate-400">Thông tin ngân hàng đã được lưu. Bạn không có quyền chỉnh sửa thông tin tài khoản</p>
                    @else
                        <button class="w-full rounded-2xl border border-white/10 bg-white/10 px-5 py-4 font-bold transition hover:bg-white/15">Lưu tài khoản ngân hàng</button>
                    @endif
                </div>
            </form>

            <template x-teleport="body">
                <div x-show="confirmBank" x-cloak x-transition.opacity class="fixed inset-0 z-[9999] flex items-start justify-center overflow-y-auto bg-black/80 px-4 py-6 backdrop-blur-xl sm:items-center" @click.self="confirmBank = false">
                    <div x-transition.scale class="glass w-full max-w-lg rounded-[28px] p-5 sm:rounded-[32px] sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/80">Xác nhận ngân hàng</p>
                            <h3 class="mt-2 text-2xl font-black">Kiểm tra lại thông tin</h3>
                        </div>
                        <button type="button" class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white/10 transition hover:bg-white/20" @click="confirmBank = false">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>

                    <div class="mt-5 rounded-[24px] border border-amber-200/20 bg-amber-300/10 p-4 text-sm leading-6 text-amber-100">
                        Thông tin này chỉ được nhập một lần duy nhất và không được phép thay đổi sau khi xác nhận.
                    </div>

                    <div class="mt-5 grid gap-3 rounded-[24px] border border-white/10 bg-black/25 p-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-400">Tên ngân hàng</span>
                            <span class="font-semibold text-white" x-text="bankName"></span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-400">Số tài khoản</span>
                            <span class="font-semibold text-white" x-text="accountNumber"></span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-400">Chủ tài khoản</span>
                            <span class="font-semibold text-white" x-text="accountHolder"></span>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <button type="button" class="rounded-2xl border border-white/10 bg-white/10 px-5 py-3 font-bold transition hover:bg-white/15" @click="confirmBank = false">
                            Sửa lại
                        </button>
                        <button type="button" class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-3 font-black shadow-glow transition hover:-translate-y-1" @click="$refs.bankForm.submit()">
                            Xác nhận lưu
                        </button>
                    </div>
                </div>
            </div>
            </template>
        </div>
    </section>

    @if($bankAccount)
        <section
            class="glass rounded-[32px] p-6"
            x-data="{
                confirmWithdrawal: false,
                withdrawalAmount: @js(old('amount_vnd', '')),
                pitRate: @js($withdrawalPitPercent),
                grossAmount() {
                    return parseInt(String(this.withdrawalAmount || '').replace(/\D/g, ''), 10) || 0;
                },
                pitAmount() {
                    return Math.floor(this.grossAmount() * this.pitRate / 100);
                },
                netAmount() {
                    return Math.max(0, this.grossAmount() - this.pitAmount());
                },
                money(value) {
                    return new Intl.NumberFormat('vi-VN').format(value) + ' đ';
                }
            }"
            @keydown.escape.window="confirmWithdrawal = false"
        >
            <div class="mb-5 flex items-center gap-3">
                <i data-lucide="banknote-arrow-down" class="h-6 w-6 text-emerald-300"></i>
                <h2 class="text-2xl font-black">Yêu cầu rút tiền</h2>
            </div>
            <form x-ref="withdrawalForm" method="post" action="{{ route('wallet.withdraw') }}" class="grid gap-4 sm:grid-cols-[1fr_auto]" @submit.prevent="confirmWithdrawal = true">
                @csrf
                <input type="hidden" name="bank_account_id" value="{{ $bankAccount->id }}">
                <div class="grid gap-2">
                    <input class="premium-input" name="amount_vnd" type="text" inputmode="numeric" autocomplete="off" pattern="[0-9.]*" data-currency-input x-model="withdrawalAmount" value="{{ old('amount_vnd') }}" placeholder="Tối thiểu 100.000 đ" required>
                    <div class="flex flex-wrap items-center justify-between gap-2 px-1 text-xs">
                        <span class="text-slate-500">Số dư khả dụng: {{ number_format($wallet->balance_vnd, 0, ',', '.') }} đ</span>
                        @error('amount_vnd')
                            <span class="font-semibold text-rose-200">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="self-start rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-6 py-4 font-black shadow-glow transition hover:-translate-y-1">Tạo yêu cầu</button>
            </form>

            <template x-teleport="body">
                <div x-show="confirmWithdrawal" x-cloak x-transition.opacity class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto bg-black/80 p-4 backdrop-blur-xl" @click.self="confirmWithdrawal = false">
                    <div x-transition.scale class="glass w-full max-w-lg rounded-[28px] p-5 sm:rounded-[32px] sm:p-7">
                        <p class="text-sm font-semibold uppercase tracking-[.22em] text-emerald-200/80">Xác nhận rút tiền</p>
                        <h3 class="mt-2 text-2xl font-black">Kiểm tra số tiền thực nhận</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-300">
                            Hệ thống khấu trừ thuế thu nhập cá nhân trước khi chuyển tiền về tài khoản ngân hàng.
                        </p>

                        <div class="mt-6 grid gap-3 rounded-[24px] border border-white/10 bg-black/25 p-4 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-slate-400">Số tiền yêu cầu rút</span>
                                <span class="font-bold text-white" x-text="money(grossAmount())"></span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-slate-400">Thuế TNCN (<span x-text="pitRate"></span>%)</span>
                                <span class="font-bold text-amber-200" x-text="'- ' + money(pitAmount())"></span>
                            </div>
                            <div class="h-px bg-white/10"></div>
                            <div class="flex items-center justify-between gap-4 text-base">
                                <span class="font-semibold text-slate-200">Số tiền thực nhận</span>
                                <span class="text-xl font-black text-emerald-200" x-text="money(netAmount())"></span>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <button type="button" class="rounded-2xl border border-white/10 bg-white/10 px-5 py-3 font-bold transition hover:bg-white/15" @click="confirmWithdrawal = false">
                                Quay lại
                            </button>
                            <button type="button" class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-3 font-black shadow-glow transition hover:-translate-y-1" @click="$refs.withdrawalForm.submit()">
                                Xác nhận
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </section>
    @endif

    <section class="glass rounded-[32px] p-6">
        <div class="mb-5 flex items-center justify-between">
            <h2 class="text-2xl font-black">Lịch sử ledger</h2>
            <i data-lucide="scroll-text" class="h-6 w-6 text-violet-200"></i>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[680px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                <tr><th class="py-3">Loại</th><th>Số tiền</th><th>Ghi chú</th></tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                @foreach($ledgerEntries as $entry)
                    @php
                        $entryTypeLabel = match ($entry->type) {
                            'withdrawal_hold' => 'Tạm giữ rút tiền',
                            'withdrawal_completed', 'withdrawal_payout' => 'Rút tiền hoàn tất',
                            'withdrawal_refund' => 'Hoàn tiền rút tiền',
                            default => $entry->type,
                        };
                        $entryMemo = $entry->memo;

                        if (in_array($entry->type, ['withdrawal_hold', 'withdrawal_completed', 'withdrawal_payout'], true) && $entry->reference instanceof \App\Models\WithdrawalRequest) {
                            $entryMemo = match ($entry->reference->status) {
                                'approved', 'transferred' => 'Đã hoàn tất việc rút tiền',
                                'rejected' => 'Tạm giữ yêu cầu rút tiền',
                                default => 'Tạm giữ yêu cầu rút tiền',
                            };
                            $entryMemo .= ' | Thuế TNCN: '
                                .number_format((int) $entry->reference->pit_amount_vnd, 0, ',', '.').' đ'
                                .' | Thực nhận: '
                                .number_format((int) ($entry->reference->net_amount_vnd ?? $entry->reference->amount_vnd), 0, ',', '.').' đ';
                            $entryTypeLabel = in_array($entry->reference->status, ['approved', 'transferred'], true) ? 'Rút tiền hoàn tất' : 'Tạm giữ rút tiền';
                        }

                        $entryMemo = $entry->memoWithTimestamp($entryMemo);
                    @endphp
                    <tr class="text-slate-300 transition hover:bg-white/[.04]">
                        <td class="py-4">{{ $entryTypeLabel }}</td>
                        <td class="{{ $entry->amount_vnd >= 0 ? 'text-emerald-200' : 'text-rose-200' }}">{{ number_format($entry->amount_vnd, 0, ',', '.') }} đ</td>
                        <td>{{ $entryMemo }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($ledgerEntries->hasPages())
            <div class="mt-5 rounded-[24px] border border-white/10 bg-black/20 p-3">
                {{ $ledgerEntries->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</div>

<script>
    document.querySelectorAll('[data-currency-input]').forEach((input) => {
        const formatVnd = () => {
            const digits = input.value.replace(/\D/g, '');
            input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        };

        input.addEventListener('input', formatVnd);
        input.addEventListener('paste', () => requestAnimationFrame(formatVnd));
        formatVnd();
    });
</script>
@endsection
