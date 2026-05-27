@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_78%_18%,rgba(217,70,239,.35),transparent_32%),radial-gradient(circle_at_18%_86%,rgba(139,92,246,.28),transparent_34%)]"></div>
        <div class="relative flex flex-col justify-between gap-6 xl:flex-row xl:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-fuchsia-200/80">Affiliate Members</p>
                <h1 class="mt-3 text-4xl font-black sm:text-6xl">Quản lý thành viên</h1>
                <p class="mt-4 max-w-2xl text-slate-300">Theo dõi những thành viên đã đăng ký qua link affiliate, trạng thái kích hoạt và hiệu suất mời trong 5 tuần gần đây.</p>
            </div>
            <div class="flex flex-wrap gap-2 rounded-[24px] border border-white/10 bg-black/25 p-2">
                @foreach(['day' => 'Ngày', 'week' => 'Tuần', 'month' => 'Tháng', 'all' => 'Tất cả'] as $key => $label)
                    <a
                        href="{{ route('affiliate.index', ['period' => $key]) }}"
                        class="rounded-2xl px-4 py-2 text-sm font-bold transition {{ $period === $key ? 'bg-gradient-to-r from-violet-500 to-fuchsia-500 text-white shadow-glow' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="grid gap-5 md:grid-cols-4">
        <article class="glass rounded-[28px] p-5 transition hover:-translate-y-1 hover:shadow-glow">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-400">Tổng đã mời</p>
                <i data-lucide="user-plus" class="h-5 w-5 text-violet-200"></i>
            </div>
            <p class="mt-3 text-4xl font-black">{{ $totalInvited }}</p>
        </article>
        <article class="glass rounded-[28px] p-5 transition hover:-translate-y-1 hover:shadow-glow">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-400">Đã kích hoạt</p>
                <i data-lucide="badge-check" class="h-5 w-5 text-emerald-200"></i>
            </div>
            <p class="mt-3 text-4xl font-black">{{ $totalActivated }}</p>
        </article>
        <article class="glass rounded-[28px] p-5 transition hover:-translate-y-1 hover:shadow-glow">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-400">Theo bộ lọc</p>
                <i data-lucide="filter" class="h-5 w-5 text-amber-200"></i>
            </div>
            <p class="mt-3 text-4xl font-black">{{ $filteredTotal }}</p>
        </article>
        <article class="glass rounded-[28px] p-5 transition hover:-translate-y-1 hover:shadow-glow">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-400">Active theo lọc</p>
                <i data-lucide="activity" class="h-5 w-5 text-fuchsia-200"></i>
            </div>
            <p class="mt-3 text-4xl font-black">{{ $filteredActive }}</p>
        </article>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">5-week trend</p>
                <h2 class="mt-2 text-2xl font-black">So sánh 5 tuần gần nhất</h2>
            </div>
            <div class="flex gap-4 text-xs text-slate-300">
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-violet-400"></span>Đã mời</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-emerald-300"></span>Đã kích hoạt</span>
            </div>
        </div>

        <div class="grid min-h-[260px] grid-cols-5 items-end gap-4 rounded-[28px] border border-white/10 bg-black/20 p-5">
            @foreach($weeks as $week)
                <div class="flex h-full flex-col justify-end gap-3">
                    <div class="flex h-48 items-end justify-center gap-2">
                        <div
                            class="w-6 rounded-t-2xl bg-gradient-to-t from-violet-700 to-violet-300 shadow-glow transition hover:scale-105"
                            style="height: {{ max(8, ($week['invited'] / $maxChartValue) * 100) }}%"
                            title="Đã mời: {{ $week['invited'] }}"
                        ></div>
                        <div
                            class="w-6 rounded-t-2xl bg-gradient-to-t from-emerald-700 to-emerald-300 shadow-lg shadow-emerald-950/40 transition hover:scale-105"
                            style="height: {{ max(8, ($week['activated'] / $maxChartValue) * 100) }}%"
                            title="Đã kích hoạt: {{ $week['activated'] }}"
                        ></div>
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-bold text-white">{{ $week['label'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $week['invited'] }}/{{ $week['activated'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-fuchsia-200/70">Member list</p>
                <h2 class="mt-2 text-2xl font-black">Thành viên đã giới thiệu</h2>
            </div>
            <i data-lucide="users-round" class="h-6 w-6 text-fuchsia-200"></i>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                <tr>
                    <th class="py-3">Thành viên</th>
                    <th>Liên hệ</th>
                    <th>Ngày đăng ký</th>
                    <th>Trạng thái đơn</th>
                    <th>Hết hạn gói</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                @forelse($referrals as $item)
                    <tr class="text-slate-300 transition hover:bg-white/[.04]">
                        <td class="py-4">
                            <div class="flex items-center gap-3">
                                <div class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-violet-500 to-fuchsia-500 font-black text-white">
                                    {{ strtoupper(substr($item['user']?->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-white">{{ $item['user']?->name ?? 'Không xác định' }}</p>
                                    <p class="text-xs text-slate-500">ID: {{ $item['user']?->username }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <p>{{ $item['display_email'] }}</p>
                            <p class="text-xs text-slate-500">{{ $item['display_phone'] }}</p>
                        </td>
                        <td>{{ $item['referral']->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($item['is_active'])
                                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-400/10 px-3 py-1 text-xs font-bold text-emerald-100">
                                    <span class="h-2 w-2 rounded-full bg-emerald-300"></span>Đã active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-2 rounded-full bg-amber-300/10 px-3 py-1 text-xs font-bold text-amber-100">
                                    <span class="h-2 w-2 rounded-full bg-amber-300"></span>Chưa active
                                </span>
                            @endif
                        </td>
                        <td>{{ $item['subscription_ends_at'] ? $item['subscription_ends_at']->format('d/m/Y') : 'Chưa có gói' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400">
                            Chưa có thành viên nào đăng ký qua link affiliate trong bộ lọc này.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
