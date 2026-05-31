@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="grid gap-5 lg:grid-cols-[1.1fr_.9fr_.9fr]">
        <div class="relative overflow-hidden rounded-[32px] border border-white/10 bg-gradient-to-br from-violet-500/20 to-sky-400/10 p-6 shadow-glow backdrop-blur-2xl sm:p-8">
            <div class="absolute -right-14 -top-14 h-48 w-48 rounded-full bg-violet-500/25 blur-3xl"></div>
            <div class="relative">
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-100/80">Transaction History</p>
                <h1 class="mt-3 text-4xl font-black sm:text-5xl">Lịch sử giao dịch</h1>
                <p class="mt-4 max-w-2xl text-slate-300">Toàn bộ giao dịch phát sinh liên quan đến tài khoản của bạn được lưu tại đây để tiện tra soát.</p>
            </div>
        </div>

        <div class="glass rounded-[32px] p-6">
            <p class="text-sm font-semibold uppercase tracking-[.22em] text-emerald-200/70">Tổng Affiliate</p>
            <p class="mt-4 text-4xl font-black text-emerald-100">{{ number_format($affiliateTotalVnd, 0, ',', '.') }}đ</p>
            <p class="mt-2 text-sm text-slate-400">Tổng hoa hồng Affiliate đã nhận thành công.</p>
        </div>

        <div class="glass rounded-[32px] p-6">
            <p class="text-sm font-semibold uppercase tracking-[.22em] text-sky-200/70">Tổng Pool Share</p>
            <p class="mt-4 text-4xl font-black text-sky-100">{{ number_format($poolShareTotalVnd, 0, ',', '.') }}đ</p>
            <p class="mt-2 text-sm text-slate-400">Tổng Pool Share đã nhận thành công.</p>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <form method="get" action="{{ route('transactions.index') }}" class="grid gap-4 lg:grid-cols-[220px_1fr_auto]">
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">Lọc loại giao dịch</span>
                <select class="premium-input" name="type">
                    <option value="all" @selected($selectedType === 'all')>Tất cả</option>
                    @foreach($typeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="grid gap-2">
                <span class="text-sm text-slate-400">Tìm kiếm theo nội dung, email, mã giao dịch</span>
                <input class="premium-input" type="text" name="q" value="{{ $search }}" placeholder="Ví dụ: affiliate, abc@gmail.com, QI12345678">
            </label>

            <div class="flex items-end gap-3">
                <button class="w-full rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1 lg:w-auto">
                    Lọc dữ liệu
                </button>
            </div>
        </form>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr>
                        <th class="py-3">STT</th>
                        <th>Loại giao dịch</th>
                        <th>Số tiền</th>
                        <th>Nội dung &amp; ghi chú</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($transactions as $index => $transaction)
                        @php
                            $rowNumber = ($transactions->firstItem() ?? 1) + $index;
                            $amountClass = $transaction->amount >= 0 ? 'text-emerald-200' : 'text-rose-200';
                            $amountPrefix = $transaction->amount >= 0 ? '+' : '-';
                            $statusClass = match ($transaction->status) {
                                \App\Models\TransactionLog::STATUS_SUCCESS => 'bg-emerald-400/10 text-emerald-100',
                                \App\Models\TransactionLog::STATUS_FAILED => 'bg-rose-400/10 text-rose-100',
                                default => 'bg-amber-300/10 text-amber-100',
                            };
                        @endphp
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4 font-semibold text-white">{{ $rowNumber }}</td>
                            <td>{{ $transaction->typeLabel() }}</td>
                            <td class="font-bold {{ $amountClass }}">{{ $amountPrefix }}{{ number_format(abs($transaction->amount), 0, ',', '.') }}đ</td>
                            <td class="max-w-lg">
                                <p class="font-medium text-white">{{ $transaction->description }}</p>
                                @if($transaction->notes || $transaction->reference_id)
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $transaction->notes }}
                                        @if($transaction->reference_id)
                                            <span class="inline-block">Mã: {{ $transaction->reference_id }}</span>
                                        @endif
                                    </p>
                                @endif
                            </td>
                            <td>{{ $transaction->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">
                                    {{ $transaction->statusLabel() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400">Chưa có giao dịch nào được ghi nhận.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="mt-5 rounded-[24px] border border-white/10 bg-black/20 p-3">
                {{ $transactions->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
