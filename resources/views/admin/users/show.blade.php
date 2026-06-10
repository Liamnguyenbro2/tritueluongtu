@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6 sm:p-8">
        <div class="flex flex-col justify-between gap-6 xl:flex-row xl:items-start">
            <div>
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[.22em] text-sky-200/80 transition hover:text-white">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i> Quay lại quản trị user
                </a>
                <p class="mt-5 text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">User Detail</p>
                <h1 class="mt-3 text-4xl font-black">Chi tiết {{ $user->name }}</h1>
                <p class="mt-3 text-slate-400">{{ $user->username }} • {{ $user->email }}</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-[24px] border border-white/10 bg-black/25 p-5">
                    <p class="text-sm text-slate-400">Ví số dư</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($wallet->balance_vnd, 0, ',', '.') }} đ</p>
                </div>
                <div class="rounded-[24px] border border-white/10 bg-black/25 p-5">
                    <p class="text-sm text-slate-400">Gói kích hoạt</p>
                    <p class="mt-2 text-2xl font-black">{{ $activePlanLabel }}</p>
                    <p class="mt-2 text-xs text-slate-500">Ngày tạo: {{ $user->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-5 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">Transaction History</p>
                <h2 class="mt-2 text-2xl font-black">Lịch sử giao dịch</h2>
            </div>
            <div class="rounded-[20px] border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-400">
                Sắp xếp mới nhất lên đầu
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr>
                        <th class="py-3">Số thứ tự</th>
                        <th>Thời gian giao dịch</th>
                        <th>Loại giao dịch</th>
                        <th>Số tiền cộng hoặc trừ</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($transactions as $index => $transaction)
                        @php
                            $rowNumber = ($transactions->firstItem() ?? 1) + $index;
                            $amountClass = $transaction->amount >= 0 ? 'text-emerald-200' : 'text-rose-200';
                            $amountPrefix = $transaction->amount >= 0 ? '+' : '-';
                        @endphp
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4 font-semibold text-white">{{ $rowNumber }}</td>
                            <td>{{ $transaction->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $transaction->typeLabel() }}</td>
                            <td class="font-bold {{ $amountClass }}">{{ $amountPrefix }}{{ number_format(abs($transaction->amount), 0, ',', '.') }}đ</td>
                            <td class="max-w-xl">
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-slate-400">User này chưa có giao dịch nào được ghi nhận.</td>
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
