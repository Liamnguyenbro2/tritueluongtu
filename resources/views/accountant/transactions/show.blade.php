@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6">
        <p class="text-sm font-semibold uppercase tracking-[.22em] text-sky-200/70">Transaction Detail</p>
        <h1 class="mt-2 text-3xl font-black">Chi tiết giao dịch</h1>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-[24px] border border-white/10 bg-black/25 p-4"><span class="text-slate-400">Mã giao dịch</span><p class="mt-2 font-semibold text-white">{{ $transaction->reference_id ?: '—' }}</p></div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 p-4"><span class="text-slate-400">User</span><p class="mt-2 font-semibold text-white">{{ $transaction->user?->name }} - {{ $transaction->user?->email }}</p></div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 p-4"><span class="text-slate-400">Loại</span><p class="mt-2 font-semibold text-white">{{ $transaction->typeLabel() }}</p></div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 p-4"><span class="text-slate-400">Số tiền</span><p class="mt-2 font-semibold {{ $transaction->amount >= 0 ? 'text-emerald-200' : 'text-rose-200' }}">{{ number_format($transaction->amount, 0, ',', '.') }}đ</p></div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 p-4 md:col-span-2"><span class="text-slate-400">Nội dung</span><p class="mt-2 font-semibold text-white">{{ $transaction->description }}</p><p class="mt-2 text-sm text-slate-400">{{ $transaction->notes ?: 'Không có ghi chú.' }}</p></div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 p-4"><span class="text-slate-400">Thời gian</span><p class="mt-2 font-semibold text-white">{{ $transaction->created_at->format('d/m/Y H:i:s') }}</p></div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 p-4"><span class="text-slate-400">Trạng thái</span><p class="mt-2 font-semibold text-white">{{ $transaction->statusLabel() }}</p></div>
        </div>
    </section>
</div>
@endsection
