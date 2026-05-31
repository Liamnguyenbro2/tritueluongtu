@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-sky-200/70">Transactions</p>
                <h1 class="mt-2 text-3xl font-black">Quản lý giao dịch</h1>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('accountant.transactions.export', ['format' => 'xlsx'] + request()->query()) }}" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-bold transition hover:bg-white/15">Xuất Excel</a>
                <a href="{{ route('accountant.transactions.export', ['format' => 'csv'] + request()->query()) }}" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-bold transition hover:bg-white/15">Xuất CSV</a>
                <a href="{{ route('accountant.transactions.export', ['format' => 'pdf'] + request()->query()) }}" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-bold transition hover:bg-white/15">Xuất PDF</a>
            </div>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <form method="get" class="grid gap-4 xl:grid-cols-[160px_160px_220px_200px_200px_1fr]">
            <input class="premium-input" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            <input class="premium-input" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            <input class="premium-input" type="text" name="user" value="{{ $filters['user'] ?? '' }}" placeholder="Theo user">
            <select class="premium-input" name="type">
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? 'all') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select class="premium-input" name="status">
                <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Tất cả trạng thái</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-3">
                <input class="premium-input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nội dung, email, mã GD">
                <button class="rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-3 font-black text-white shadow-glow">Lọc</button>
            </div>
        </form>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr>
                        <th class="py-3">STT</th>
                        <th>Mã GD</th>
                        <th>User</th>
                        <th>Loại</th>
                        <th>Số tiền</th>
                        <th>Nội dung</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($transactions as $index => $transaction)
                        @php
                            $rowNumber = ($transactions->firstItem() ?? 1) + $index;
                            $statusClass = match ($transaction->status) {
                                \App\Models\TransactionLog::STATUS_SUCCESS => 'bg-emerald-400/10 text-emerald-100',
                                \App\Models\TransactionLog::STATUS_FAILED => 'bg-rose-400/10 text-rose-100',
                                default => 'bg-amber-300/10 text-amber-100',
                            };
                        @endphp
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4 font-semibold text-white">{{ $rowNumber }}</td>
                            <td>{{ $transaction->reference_id ?: '—' }}</td>
                            <td>{{ $transaction->user?->email }}</td>
                            <td>{{ $transaction->typeLabel() }}</td>
                            <td class="{{ $transaction->amount >= 0 ? 'text-emerald-200' : 'text-rose-200' }}">{{ $transaction->amount >= 0 ? '+' : '-' }}{{ number_format(abs($transaction->amount), 0, ',', '.') }}đ</td>
                            <td class="max-w-md">
                                <p class="font-medium text-white">{{ $transaction->description }}</p>
                                @if($transaction->notes)
                                    <p class="mt-1 text-xs text-slate-500">{{ $transaction->notes }}</p>
                                @endif
                            </td>
                            <td>{{ $transaction->created_at->format('d/m/Y H:i:s') }}</td>
                            <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $transaction->statusLabel() }}</span></td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="{{ route('accountant.transactions.show', $transaction) }}" class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-bold transition hover:bg-white/15">Chi tiết</a>
                                    <a href="{{ route('accountant.transactions.invoice', $transaction) }}" target="_blank" class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-bold transition hover:bg-white/15">In hóa đơn</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-8 text-center text-slate-400">Chưa có giao dịch nào.</td></tr>
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
