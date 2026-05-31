@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-cyan-200/70">Reports</p>
                <h1 class="mt-2 text-3xl font-black">Bao cao tai chinh</h1>
            </div>
            <div class="flex flex-wrap gap-3">
                <form method="get" class="flex gap-3">
                    <select class="premium-input w-48" name="period">
                        @foreach(['day' => 'Theo ngay', 'week' => 'Theo tuan', 'month' => 'Theo thang', 'year' => 'Theo nam'] as $value => $label)
                            <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-2xl bg-gradient-to-r from-cyan-500 to-violet-500 px-5 py-3 font-black text-white shadow-glow">Ap dung</button>
                </form>
                <a href="{{ route('accountant.reports.export', ['format' => 'xlsx', 'period' => $period]) }}" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-bold transition hover:bg-white/15">Excel</a>
                <a href="{{ route('accountant.reports.export', ['format' => 'csv', 'period' => $period]) }}" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-bold transition hover:bg-white/15">CSV</a>
                <a href="{{ route('accountant.reports.export', ['format' => 'pdf', 'period' => $period]) }}" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-bold transition hover:bg-white/15">PDF</a>
            </div>
        </div>
    </section>

    <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="glass rounded-[28px] p-5"><p class="text-xs uppercase tracking-[.18em] text-slate-400">{{ $label }}</p><p class="mt-3 text-3xl font-black">{{ number_format($revenue, 0, ',', '.') }}d</p><p class="mt-2 text-sm text-slate-400">Doanh thu</p></div>
        <div class="glass rounded-[28px] p-5"><p class="text-xs uppercase tracking-[.18em] text-slate-400">{{ $label }}</p><p class="mt-3 text-3xl font-black text-emerald-100">{{ number_format($topup, 0, ',', '.') }}d</p><p class="mt-2 text-sm text-slate-400">Tong nap</p></div>
        <div class="glass rounded-[28px] p-5"><p class="text-xs uppercase tracking-[.18em] text-slate-400">{{ $label }}</p><p class="mt-3 text-3xl font-black text-rose-100">{{ number_format($withdraw, 0, ',', '.') }}d</p><p class="mt-2 text-sm text-slate-400">Tong rut</p></div>
        <div class="glass rounded-[28px] p-5"><p class="text-xs uppercase tracking-[.18em] text-slate-400">{{ $label }}</p><p class="mt-3 text-3xl font-black text-amber-100">{{ number_format($profit, 0, ',', '.') }}d</p><p class="mt-2 text-sm text-slate-400">Loi nhuan thuan</p></div>
    </section>

    <section class="grid gap-5 xl:grid-cols-3">
        <div class="glass rounded-[32px] p-6">
            <h2 class="text-xl font-black">Khach nap nhieu nhat</h2>
            <div class="mt-4 space-y-3">
                @forelse($topDepositors as $row)
                    <div class="rounded-[20px] border border-white/10 bg-black/20 p-4">
                        <div class="font-semibold text-white">{{ $row->user?->name ?? 'N/A' }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $row->user?->email }}</div>
                        <div class="mt-3 text-emerald-100">{{ number_format((int) $row->total_amount, 0, ',', '.') }}d</div>
                    </div>
                @empty
                    <div class="rounded-[20px] border border-white/10 bg-black/20 p-4 text-sm text-slate-400">Chua co du lieu.</div>
                @endforelse
            </div>
        </div>

        <div class="glass rounded-[32px] p-6">
            <h2 class="text-xl font-black">Khach rut nhieu nhat</h2>
            <div class="mt-4 space-y-3">
                @forelse($topWithdrawers as $row)
                    <div class="rounded-[20px] border border-white/10 bg-black/20 p-4">
                        <div class="font-semibold text-white">{{ $row->user?->name ?? 'N/A' }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $row->user?->email }}</div>
                        <div class="mt-3 text-rose-100">{{ number_format((int) $row->total_amount, 0, ',', '.') }}d</div>
                    </div>
                @empty
                    <div class="rounded-[20px] border border-white/10 bg-black/20 p-4 text-sm text-slate-400">Chua co du lieu.</div>
                @endforelse
            </div>
        </div>

        <div class="glass rounded-[32px] p-6">
            <h2 class="text-xl font-black">Khach su dung dich vu nhieu nhat</h2>
            <div class="mt-4 space-y-3">
                @forelse($topServiceUsers as $row)
                    <div class="rounded-[20px] border border-white/10 bg-black/20 p-4">
                        <div class="font-semibold text-white">{{ $row->user?->name ?? 'N/A' }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $row->user?->email }}</div>
                        <div class="mt-3 text-cyan-100">{{ $row->usage_count }} giao dich</div>
                    </div>
                @empty
                    <div class="rounded-[20px] border border-white/10 bg-black/20 p-4 text-sm text-slate-400">Chua co du lieu.</div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
