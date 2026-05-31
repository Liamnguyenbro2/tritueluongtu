@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(14,165,233,.18),transparent_30%),radial-gradient(circle_at_18%_72%,rgba(139,92,246,.22),transparent_30%)]"></div>
        <div class="relative">
            <p class="text-sm font-semibold uppercase tracking-[.24em] text-sky-200/80">Financial Dashboard</p>
            <h1 class="mt-3 text-4xl font-black sm:text-6xl">Kế toán</h1>
            <p class="mt-4 max-w-3xl text-slate-300">Bảng điều khiển tài chính dành riêng cho Accountant: theo dõi doanh thu, giao dịch, nạp/rút tiền và các hành động đối soát quan trọng.</p>
        </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-3">
        <div class="glass rounded-[32px] p-6 xl:col-span-2">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-emerald-200/70">Hôm nay</p>
                    <h2 class="mt-2 text-2xl font-black">Thống kê tài chính</h2>
                </div>
                <i data-lucide="banknote-arrow-up" class="h-6 w-6 text-emerald-200"></i>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-[24px] border border-white/10 bg-black/25 p-4">
                    <p class="text-xs uppercase tracking-[.18em] text-slate-400">Doanh thu hôm nay</p>
                    <p class="mt-3 text-2xl font-black text-white">{{ number_format($todayRevenue, 0, ',', '.') }}đ</p>
                </div>
                <div class="rounded-[24px] border border-white/10 bg-black/25 p-4">
                    <p class="text-xs uppercase tracking-[.18em] text-slate-400">Tổng tiền nạp</p>
                    <p class="mt-3 text-2xl font-black text-emerald-100">{{ number_format($todayTopup, 0, ',', '.') }}đ</p>
                </div>
                <div class="rounded-[24px] border border-white/10 bg-black/25 p-4">
                    <p class="text-xs uppercase tracking-[.18em] text-slate-400">Tổng tiền rút</p>
                    <p class="mt-3 text-2xl font-black text-rose-100">{{ number_format($todayWithdraw, 0, ',', '.') }}đ</p>
                </div>
                <div class="rounded-[24px] border border-white/10 bg-black/25 p-4">
                    <p class="text-xs uppercase tracking-[.18em] text-slate-400">Lợi nhuận thuần</p>
                    <p class="mt-3 text-2xl font-black text-amber-100">{{ number_format($todayNetProfit, 0, ',', '.') }}đ</p>
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div class="rounded-[24px] border border-emerald-300/20 bg-emerald-400/10 p-4">
                    <p class="text-xs uppercase tracking-[.18em] text-emerald-100/70">Thành công</p>
                    <p class="mt-3 text-3xl font-black text-emerald-100">{{ $successCount }}</p>
                </div>
                <div class="rounded-[24px] border border-rose-300/20 bg-rose-400/10 p-4">
                    <p class="text-xs uppercase tracking-[.18em] text-rose-100/70">Thất bại</p>
                    <p class="mt-3 text-3xl font-black text-rose-100">{{ $failedCount }}</p>
                </div>
                <div class="rounded-[24px] border border-amber-300/20 bg-amber-300/10 p-4">
                    <p class="text-xs uppercase tracking-[.18em] text-amber-100/70">Chờ xử lý</p>
                    <p class="mt-3 text-3xl font-black text-amber-100">{{ $pendingCount }}</p>
                </div>
            </div>
        </div>

        <div class="glass rounded-[32px] p-6">
            <p class="text-sm font-semibold uppercase tracking-[.22em] text-violet-200/70">Tháng này</p>
            <h2 class="mt-2 text-2xl font-black">Tổng quan tháng</h2>
            <div class="mt-5 space-y-4">
                <div class="rounded-[24px] border border-white/10 bg-black/25 p-4">
                    <p class="text-xs uppercase tracking-[.18em] text-slate-400">Doanh thu tháng</p>
                    <p class="mt-3 text-2xl font-black">{{ number_format($monthRevenue, 0, ',', '.') }}đ</p>
                </div>
                <div class="rounded-[24px] border border-white/10 bg-black/25 p-4">
                    <p class="text-xs uppercase tracking-[.18em] text-slate-400">Tổng nạp tháng</p>
                    <p class="mt-3 text-2xl font-black text-emerald-100">{{ number_format($monthTopup, 0, ',', '.') }}đ</p>
                </div>
                <div class="rounded-[24px] border border-white/10 bg-black/25 p-4">
                    <p class="text-xs uppercase tracking-[.18em] text-slate-400">Tổng rút tháng</p>
                    <p class="mt-3 text-2xl font-black text-rose-100">{{ number_format($monthWithdraw, 0, ',', '.') }}đ</p>
                </div>
                <div class="rounded-[24px] border {{ $growthPercent >= 0 ? 'border-emerald-300/20 bg-emerald-400/10' : 'border-rose-300/20 bg-rose-400/10' }} p-4">
                    <p class="text-xs uppercase tracking-[.18em] text-slate-300">Tăng trưởng so với tháng trước</p>
                    <p class="mt-3 text-2xl font-black {{ $growthPercent >= 0 ? 'text-emerald-100' : 'text-rose-100' }}">{{ $growthPercent >= 0 ? '+' : '' }}{{ $growthPercent }}%</p>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.2fr_.8fr]">
        <div class="glass rounded-[32px] p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-cyan-200/70">Biểu đồ</p>
                    <h2 class="mt-2 text-2xl font-black">Doanh thu theo ngày</h2>
                </div>
                <i data-lucide="chart-column" class="h-6 w-6 text-cyan-200"></i>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-7">
                @foreach($dailyRevenue as $row)
                    <div class="rounded-[24px] border border-white/10 bg-black/25 p-3">
                        <p class="text-center text-xs text-slate-400">{{ $row['label'] }}</p>
                        <div class="mt-3 flex h-40 items-end justify-center">
                            <div class="w-8 rounded-full bg-gradient-to-t from-violet-500 to-cyan-300" style="height: {{ max(12, intval(($row['revenue'] / $maxRevenue) * 140)) }}px"></div>
                        </div>
                        <p class="mt-3 text-center text-xs font-semibold text-white">{{ number_format($row['revenue'], 0, ',', '.') }}đ</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="glass rounded-[32px] p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/70">Top khách hàng</p>
                    <h2 class="mt-2 text-2xl font-black">Giao dịch nhiều nhất</h2>
                </div>
                <i data-lucide="users" class="h-6 w-6 text-amber-200"></i>
            </div>

            <div class="mt-5 space-y-3">
                @forelse($topCustomers as $customer)
                    <div class="rounded-[24px] border border-white/10 bg-black/25 p-4">
                        <p class="font-semibold text-white">{{ $customer->user?->name ?? 'Không xác định' }}</p>
                        <p class="mt-1 text-sm text-slate-400">{{ $customer->user?->email }}</p>
                        <div class="mt-3 flex items-center justify-between text-sm">
                            <span class="text-slate-400">{{ $customer->transaction_count }} giao dịch</span>
                            <span class="font-semibold text-amber-100">{{ number_format((int) $customer->total_amount, 0, ',', '.') }}đ</span>
                        </div>
                    </div>
                @empty
                    <p class="rounded-[24px] border border-white/10 bg-black/25 p-4 text-sm text-slate-400">Chưa có dữ liệu giao dịch để thống kê.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-violet-200/70">Audit Log</p>
                <h2 class="mt-2 text-2xl font-black">Nhật ký kế toán gần nhất</h2>
            </div>
            <a href="{{ route('accountant.audit-logs') }}" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-sm font-bold transition hover:bg-white/15">Xem tất cả</a>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr>
                        <th class="py-3">Thời gian</th>
                        <th>Người thao tác</th>
                        <th>Hành động</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($latestAudits as $log)
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->actor?->name }}<div class="text-xs text-slate-500">{{ $log->actor?->email }}</div></td>
                            <td class="font-medium text-white">{{ $log->description }}</td>
                            <td>{{ $log->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-slate-400">Chưa có audit log.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
