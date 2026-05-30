@php
    $fmt = fn ($amount) => number_format((int) $amount, 0, ',', '.').' đ';
    $selectedSnapshot ??= null;
@endphp

@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6 sm:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-fuchsia-200/70">Snapshot Report</p>
                <h1 class="mt-2 text-3xl font-black sm:text-5xl">Report báo cáo ngày</h1>
                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-300 sm:text-base">
                    Mọi dữ liệu ở trang này chỉ lấy từ snapshot đã tổng hợp lúc 23:59. Hệ thống tự giữ 10 ngày gần nhất và xóa snapshot cũ hơn.
                </p>
            </div>

            @if($selectedSnapshot)
                <div class="flex flex-col gap-3 sm:flex-row">
                    <form method="get" action="{{ route('admin.reports.index') }}">
                        <select class="premium-input min-w-[220px]" name="date" onchange="this.form.submit()">
                            @foreach($snapshots as $snapshot)
                                <option value="{{ $snapshot->report_date->toDateString() }}" @selected($selectedSnapshot->is($snapshot))>
                                    {{ $snapshot->report_date->format('d/m/Y') }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('admin.reports.export', $selectedSnapshot) }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                        <i data-lucide="file-spreadsheet" class="h-5 w-5"></i> Xuất Excel
                    </a>
                </div>
            @endif
        </div>
    </section>

    @if(!$selectedSnapshot)
        <section class="glass rounded-[32px] p-10 text-center text-slate-300">
            Chưa có snapshot báo cáo nào. Hãy đợi scheduler chạy lúc 23:59 hoặc chạy tay lệnh <code class="rounded bg-white/10 px-2 py-1">php artisan admin-reports:snapshot 2026-05-30</code>.
        </section>
    @else
        <section class="grid gap-5 xl:grid-cols-4">
            <article class="glass rounded-[28px] p-5">
                <p class="text-sm text-slate-400">Ngày snapshot</p>
                <p class="mt-3 text-3xl font-black">{{ $selectedSnapshot->report_date->format('d/m/Y') }}</p>
                <p class="mt-2 text-sm text-slate-500">Tổng hợp lúc {{ $selectedSnapshot->captured_at?->format('H:i d/m/Y') }}</p>
            </article>
            <article class="glass rounded-[28px] p-5">
                <p class="text-sm text-slate-400">Thành viên kích hoạt mới</p>
                <p class="mt-3 text-3xl font-black">{{ number_format($selectedSnapshot->new_paid_members_count, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Số lần kích hoạt trả phí: {{ number_format($selectedSnapshot->activation_count, 0, ',', '.') }}</p>
            </article>
            <article class="glass rounded-[28px] p-5">
                <p class="text-sm text-slate-400">Tổng doanh số kích hoạt</p>
                <p class="mt-3 text-3xl font-black">{{ $fmt($selectedSnapshot->gross_sales_vnd) }}</p>
                <p class="mt-2 text-sm text-slate-500">Snapshot chỉ đọc dữ liệu đã chốt ngày.</p>
            </article>
            <article class="glass rounded-[28px] p-5">
                <p class="text-sm text-slate-400">Pool Share còn lại</p>
                <p class="mt-3 text-3xl font-black">{{ $fmt($selectedSnapshot->shared_pool_balance_vnd) }}</p>
                <p class="mt-2 text-sm text-slate-500">Số dư ví shared_pool cuối ngày.</p>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-4">
            <article class="glass rounded-[28px] p-5">
                <p class="text-sm text-slate-400">Affiliate đã chi</p>
                <p class="mt-3 text-3xl font-black">{{ $fmt($selectedSnapshot->affiliate_commission_vnd) }}</p>
                <p class="mt-2 text-sm text-slate-500">Công thức 30%</p>
            </article>
            <article class="glass rounded-[28px] p-5">
                <p class="text-sm text-slate-400">VAT ghi nhận</p>
                <p class="mt-3 text-3xl font-black">{{ $fmt($selectedSnapshot->vat_vnd) }}</p>
                <p class="mt-2 text-sm text-slate-500">Công thức 10%</p>
            </article>
            <article class="glass rounded-[28px] p-5">
                <p class="text-sm text-slate-400">Doanh thu công ty</p>
                <p class="mt-3 text-3xl font-black">{{ $fmt($selectedSnapshot->company_revenue_vnd) }}</p>
                <p class="mt-2 text-sm text-slate-500">Công thức 45%</p>
            </article>
            <article class="glass rounded-[28px] p-5">
                <p class="text-sm text-slate-400">Pool Share phát sinh</p>
                <p class="mt-3 text-3xl font-black">{{ $fmt($selectedSnapshot->pool_share_in_vnd) }}</p>
                <p class="mt-2 text-sm text-slate-500">Công thức 15%</p>
            </article>
        </section>

        <section class="glass rounded-[32px] p-6 sm:p-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-violet-200/70">Pool Share 23:59</p>
                    <h2 class="mt-2 text-2xl font-black">Thống kê nhóm A / B / C</h2>
                </div>
                <div class="rounded-[20px] border border-white/10 bg-black/20 px-4 py-3 text-right">
                    <p class="text-xs uppercase tracking-[.18em] text-slate-500">Tổng chi Pool Share</p>
                    <p class="mt-1 text-xl font-black">{{ $fmt($selectedSnapshot->pool_share_distributed_vnd) }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-5 xl:grid-cols-3">
                @foreach($selectedGroupStats as $group => $stats)
                    <article class="rounded-[28px] border border-white/10 bg-black/20 p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-2xl font-black">Nhóm {{ $group }}</h3>
                            <span class="rounded-full bg-violet-400/10 px-3 py-1 text-xs font-bold text-violet-100">{{ number_format($stats['share_bp'] / 100, 1, '.', '') }}%</span>
                        </div>
                        <p class="mt-4 text-sm leading-7 text-slate-300">
                            Điều kiện:
                            @if($stats['max'] === null)
                                từ {{ number_format($stats['min'], 0, ',', '.') }} F1 kích hoạt trả phí trở lên
                            @else
                                từ {{ number_format($stats['min'], 0, ',', '.') }} đến {{ number_format($stats['max'], 0, ',', '.') }} F1 kích hoạt trả phí
                            @endif
                            và tài khoản còn hiệu lực gói.
                        </p>
                        <div class="mt-5 grid gap-3 text-sm text-slate-300">
                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/[.03] px-4 py-3">
                                <span>Số thành viên đủ điều kiện</span>
                                <span class="font-black text-white">{{ number_format($stats['qualified_count'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/[.03] px-4 py-3">
                                <span>Quỹ chia nhóm</span>
                                <span class="font-black text-white">{{ $fmt($stats['group_total_vnd']) }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/[.03] px-4 py-3">
                                <span>Tiền mỗi người</span>
                                <span class="font-black text-white">{{ $fmt($stats['amount_each_vnd']) }}</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.08fr_.92fr]">
            <div class="glass rounded-[32px] p-6">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/70">Pool Share Export</p>
                        <h2 class="mt-2 text-2xl font-black">Danh sách nhận Pool Share</h2>
                    </div>
                    <span class="rounded-full border border-white/10 bg-white/[.04] px-4 py-2 text-sm font-bold text-slate-300">{{ $selectedSnapshot->poolShareRows->count() }} dòng</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                            <tr>
                                <th class="py-3">ID</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>Nhóm</th>
                                <th>F1 hợp lệ</th>
                                <th>Tiền nhận</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            @forelse($selectedSnapshot->poolShareRows as $row)
                                <tr class="text-slate-300">
                                    <td class="py-4">#{{ $row->user_id }}</td>
                                    <td class="font-semibold text-white">{{ $row->name }}</td>
                                    <td>{{ $row->email }}</td>
                                    <td><span class="rounded-full bg-violet-400/10 px-3 py-1 text-xs font-bold text-violet-100">{{ $row->group_code }}</span></td>
                                    <td>{{ number_format($row->active_referrals_count, 0, ',', '.') }}</td>
                                    <td class="font-bold text-emerald-200">{{ $fmt($row->payout_vnd) }}</td>
                                    <td>{{ $row->account_status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400">Ngày này chưa có thành viên nhận Pool Share.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass rounded-[32px] p-6">
                <div class="mb-5">
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-sky-200/70">Log đối soát</p>
                    <h2 class="mt-2 text-2xl font-black">Chi tiết phát sinh trong ngày</h2>
                </div>

                <div class="space-y-5">
                    @foreach($logTypeLabels as $type => $label)
                        <article class="rounded-[24px] border border-white/10 bg-black/20 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-lg font-black">{{ $label }}</h3>
                                <span class="rounded-full bg-white/[.05] px-3 py-1 text-xs font-bold text-slate-300">
                                    {{ number_format(optional($selectedLogGroups->get($type))->count() ?? 0, 0, ',', '.') }} log
                                </span>
                            </div>
                            <div class="mt-4 space-y-3">
                                @forelse($selectedLogGroups->get($type, collect()) as $log)
                                    <div class="rounded-2xl border border-white/10 bg-white/[.03] px-4 py-3">
                                        <div class="flex items-start justify-between gap-4">
                                            <p class="text-sm leading-7 text-slate-300">{{ $log->memo }}</p>
                                            <div class="shrink-0 text-right">
                                                <p class="font-bold text-white">{{ $fmt($log->amount_vnd) }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $log->occurred_at?->format('d/m/Y H:i') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">Không có log phát sinh.</p>
                                @endforelse
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
