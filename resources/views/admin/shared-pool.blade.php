@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_82%_22%,rgba(56,189,248,.18),transparent_28%),radial-gradient(circle_at_15%_80%,rgba(245,158,11,.18),transparent_26%)]"></div>
        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[.22em] text-sky-200/80 transition hover:text-white">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i> Quay lại Admin
                </a>
                <p class="mt-4 text-sm font-semibold uppercase tracking-[.24em] text-sky-200/80">Shared Pool Ledger</p>
                <h1 class="mt-3 text-4xl font-black sm:text-5xl">Lịch sử đồng chia</h1>
                <p class="mt-4 max-w-2xl text-slate-300">Theo dõi tiền vào pool, tiền đã chia cho thành viên và số dư cuối từng ngày để đối soát nhanh ngay trong trang admin.</p>
            </div>
            <div class="rounded-[28px] border border-white/10 bg-black/25 p-5 xl:w-[320px]">
                <p class="text-sm text-slate-400">Số dư shared_pool hiện tại</p>
                <p class="mt-2 text-4xl font-black">{{ number_format($sharedPool->balance_vnd, 0, ',', '.') }} đ</p>
                <p class="mt-3 text-sm text-slate-500">
                    @if($lastDistributionAt)
                        Lần chia gần nhất: {{ $lastDistributionAt->format('d/m/Y H:i') }}
                    @else
                        Chưa có lần chia nào.
                    @endif
                </p>
            </div>
        </div>
    </section>

    <section class="grid gap-5 md:grid-cols-4">
        <article class="glass rounded-[28px] p-5">
            <p class="text-sm text-slate-400">Tổng tiền vào pool</p>
            <p class="mt-3 text-3xl font-black">{{ number_format($totalPoolInVnd, 0, ',', '.') }} đ</p>
        </article>
        <article class="glass rounded-[28px] p-5">
            <p class="text-sm text-slate-400">Tổng đã chia</p>
            <p class="mt-3 text-3xl font-black">{{ number_format($totalDistributedVnd, 0, ',', '.') }} đ</p>
        </article>
        <article class="glass rounded-[28px] p-5">
            <p class="text-sm text-slate-400">Ngày đã chạy chia</p>
            <p class="mt-3 text-3xl font-black">{{ $distributedDays }}</p>
        </article>
        <article class="glass rounded-[28px] p-5">
            <p class="text-sm text-slate-400">Số dư còn lại</p>
            <p class="mt-3 text-3xl font-black">{{ number_format($sharedPool->balance_vnd, 0, ',', '.') }} đ</p>
        </article>
    </section>

    @forelse($historyDays as $day)
        @php
            $statusLabel = match ($day->status) {
                'distributed' => 'Đã chia',
                'pending' => 'Có tiền vào pool nhưng chưa chia',
                default => 'Không phát sinh chia',
            };
            $statusClass = match ($day->status) {
                'distributed' => 'bg-emerald-400/10 text-emerald-100',
                'pending' => 'bg-amber-300/10 text-amber-100',
                default => 'bg-slate-500/10 text-slate-300',
            };
        @endphp

        <article class="glass rounded-[32px] p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.24em] text-amber-200/80">Ngày {{ $day->date->format('d/m/Y') }}</p>
                    <h2 class="mt-2 text-3xl font-black">Dòng tiền đồng chia</h2>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                        <span class="rounded-full bg-white/5 px-3 py-1 text-xs font-semibold text-slate-300">
                            {{ $day->payout_count }} tài khoản nhận
                        </span>
                    </div>
                </div>
                <div class="rounded-[24px] border border-white/10 bg-black/25 p-5 xl:w-[280px]">
                    <p class="text-sm text-slate-400">Số dư cuối ngày</p>
                    <p class="mt-2 text-3xl font-black">{{ number_format($day->closing_balance_vnd, 0, ',', '.') }} đ</p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-4">
                <div class="rounded-[24px] border border-white/10 bg-black/20 p-4">
                    <p class="text-sm text-slate-400">Số dư đầu ngày</p>
                    <p class="mt-2 text-2xl font-black">{{ number_format($day->opening_balance_vnd, 0, ',', '.') }} đ</p>
                </div>
                <div class="rounded-[24px] border border-emerald-300/20 bg-emerald-500/10 p-4">
                    <p class="text-sm text-emerald-100/80">Tiền vào pool</p>
                    <p class="mt-2 text-2xl font-black text-emerald-50">{{ number_format($day->pool_in_vnd, 0, ',', '.') }} đ</p>
                </div>
                <div class="rounded-[24px] border border-sky-300/20 bg-sky-500/10 p-4">
                    <p class="text-sm text-sky-100/80">Đã chia trong ngày</p>
                    <p class="mt-2 text-2xl font-black text-sky-50">{{ number_format($day->distributed_vnd, 0, ',', '.') }} đ</p>
                </div>
                <div class="rounded-[24px] border border-amber-300/20 bg-amber-500/10 p-4">
                    <p class="text-sm text-amber-100/80">Dư giữ lại cuối ngày</p>
                    <p class="mt-2 text-2xl font-black text-amber-50">{{ number_format($day->closing_balance_vnd, 0, ',', '.') }} đ</p>
                </div>
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="text-xl font-black">Lịch sử đã chia</h3>
                    <p class="text-sm text-slate-500">Chi tiết user nhận đồng chia trong ngày</p>
                </div>

                @if($day->payouts->isNotEmpty())
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                            <tr>
                                <th class="py-3">User</th>
                                <th>Email</th>
                                <th>Tiền nhận</th>
                                <th>Thời gian</th>
                                <th>Ghi chú</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                            @foreach($day->payouts as $payout)
                                <tr class="text-slate-300 transition hover:bg-white/[.04]">
                                    <td class="py-4 font-bold text-white">#{{ $payout->user_id }} - {{ $payout->user_name }}</td>
                                    <td>{{ $payout->user_email }}</td>
                                    <td>{{ number_format($payout->amount_vnd, 0, ',', '.') }} đ</td>
                                    <td>{{ $payout->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $payout->memo }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="mt-4 rounded-[24px] border border-white/10 bg-black/20 p-4 text-sm text-slate-400">
                        Ngày này chưa có bút toán <code>pool_share_payout</code> cho user.
                    </div>
                @endif
            </div>
        </article>
    @empty
        <section class="glass rounded-[32px] p-6 text-sm text-slate-400">
            Chưa có dữ liệu shared_pool để hiển thị lịch sử.
        </section>
    @endforelse
</div>
@endsection
