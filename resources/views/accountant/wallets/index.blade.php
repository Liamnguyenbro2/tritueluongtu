@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/70">Customer Wallets</p>
                <h1 class="mt-2 text-3xl font-black">Quản lý số dư khách hàng</h1>
            </div>
            <form method="get" class="flex gap-3">
                <input class="premium-input w-[320px]" type="text" name="q" value="{{ $search }}" placeholder="Tìm theo tên, email, username">
                <button class="rounded-2xl bg-gradient-to-r from-amber-500 to-fuchsia-500 px-5 py-3 font-black text-white shadow-glow">Tìm</button>
            </form>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1200px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr>
                        <th class="py-3">User</th>
                        <th>Số dư hiện tại</th>
                        <th>Đã nạp</th>
                        <th>Đã rút</th>
                        <th>Chi tiêu</th>
                        <th>Trạng thái ví</th>
                        <th>Điều chỉnh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($users as $user)
                        @php
                            $stats = $walletStats[$user->id] ?? null;
                            $wallet = $user->wallet;
                        @endphp
                        <tr class="align-top text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4">
                                <div class="font-semibold text-white">{{ $user->name }}</div>
                                <div class="text-xs text-slate-500">{{ $user->email }}</div>
                            </td>
                            <td class="font-semibold text-white">{{ number_format($wallet?->balance_vnd ?? 0, 0, ',', '.') }}đ</td>
                            <td class="text-emerald-100">{{ number_format((int) ($stats->deposited_total ?? 0), 0, ',', '.') }}đ</td>
                            <td class="text-rose-100">{{ number_format((int) ($stats->withdrawn_total ?? 0), 0, ',', '.') }}đ</td>
                            <td class="text-amber-100">{{ number_format((int) ($stats->spent_total ?? 0), 0, ',', '.') }}đ</td>
                            <td>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ ($wallet?->is_locked ?? false) ? 'bg-rose-400/10 text-rose-100' : 'bg-emerald-400/10 text-emerald-100' }}">
                                    {{ ($wallet?->is_locked ?? false) ? 'Đã khóa' : 'Đang mở' }}
                                </span>
                            </td>
                            <td>
                                <div class="grid gap-3 xl:grid-cols-[1fr_auto]">
                                    <form method="post" action="{{ route('accountant.wallets.adjust', $user) }}" class="grid gap-2 sm:grid-cols-[120px_140px_1fr_auto]">
                                        @csrf
                                        <select class="premium-input px-3 py-2 text-sm" name="direction">
                                            <option value="add">Cộng tiền</option>
                                            <option value="subtract">Trừ tiền</option>
                                        </select>
                                        <input class="premium-input px-3 py-2 text-sm" type="number" min="1000" step="1000" name="amount_vnd" placeholder="Số tiền" required>
                                        <input class="premium-input px-3 py-2 text-sm" type="text" name="note" placeholder="Ghi chú">
                                        <button class="rounded-xl border border-white/10 bg-white/10 px-4 py-2 text-xs font-bold transition hover:bg-white/15">Cập nhật</button>
                                    </form>
                                    <form method="post" action="{{ route('accountant.wallets.toggle-lock', $user) }}">
                                        @csrf
                                        <button class="rounded-xl border px-4 py-2 text-xs font-bold transition {{ ($wallet?->is_locked ?? false) ? 'border-emerald-300/20 bg-emerald-400/10 text-emerald-100 hover:bg-emerald-400/15' : 'border-rose-300/20 bg-rose-400/10 text-rose-100 hover:bg-rose-400/15' }}">
                                            {{ ($wallet?->is_locked ?? false) ? 'Mở ví' : 'Khóa ví' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-400">Chưa có ví khách hàng nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="mt-5 rounded-[24px] border border-white/10 bg-black/20 p-3">
                {{ $users->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
