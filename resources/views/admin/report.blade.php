@extends('layouts.app')

@section('content')
@php
    $engagementTotal = max(1, $totalInvited + $totalActivated);
    $invitedPercent = round(($totalInvited / $engagementTotal) * 100, 1);
    $activatedPercent = round(($totalActivated / $engagementTotal) * 100, 1);
    $incomeTotal = max(1, $affiliateIncomeVnd + $sharedPoolIncomeVnd);
    $affiliatePercent = round(($affiliateIncomeVnd / $incomeTotal) * 100, 1);
    $poolPercent = round(($sharedPoolIncomeVnd / $incomeTotal) * 100, 1);
@endphp

<div class="space-y-8">
    <section class="glass rounded-[32px] p-6 sm:p-8">
        <div class="flex flex-col justify-between gap-6 xl:flex-row xl:items-start">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">User intelligence</p>
                <h1 class="mt-3 text-4xl font-black">Báo cáo {{ $user->name }}</h1>
                <p class="mt-3 text-slate-400">{{ $user->email }} • {{ $user->phone }}</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 p-5">
                <p class="text-sm text-slate-400">Ví số dư hiện tại</p>
                <p class="mt-2 text-3xl font-black">{{ number_format($wallet->balance_vnd, 0, ',', '.') }} đ</p>
            </div>
        </div>
    </section>

    <section class="grid gap-5 md:grid-cols-4">
        <div class="glass rounded-[28px] p-5">
            <p class="text-sm text-slate-400">Tổng đã mời</p>
            <p class="mt-3 text-4xl font-black">{{ $totalInvited }}</p>
        </div>
        <div class="glass rounded-[28px] p-5">
            <p class="text-sm text-slate-400">Đã kích hoạt</p>
            <p class="mt-3 text-4xl font-black">{{ $totalActivated }}</p>
        </div>
        <div class="glass rounded-[28px] p-5">
            <p class="text-sm text-slate-400">Theo bộ lọc</p>
            <p class="mt-3 text-4xl font-black">{{ $filteredTotal }}</p>
        </div>
        <div class="glass rounded-[28px] p-5">
            <p class="text-sm text-slate-400">Mức hoa hồng</p>
            <p class="mt-3 text-4xl font-black">{{ $referralPercent }}%</p>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-6 flex flex-col justify-between gap-4 xl:flex-row xl:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">Report mode</p>
                <h2 class="mt-2 text-2xl font-black">Báo cáo affiliate</h2>
            </div>
            <div class="flex flex-wrap gap-2 rounded-[24px] border border-white/10 bg-black/25 p-2">
                @foreach(['day' => 'Ngày', 'week' => 'Tuần', 'month' => 'Tháng', 'all' => 'Tất cả'] as $key => $label)
                    <a
                        href="{{ route('admin.users.report', ['user' => $user, 'period' => $key]) }}"
                        class="rounded-2xl px-4 py-2 text-sm font-bold transition {{ $period === $key ? 'bg-gradient-to-r from-violet-500 to-fuchsia-500 text-white shadow-glow' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        @if($period === 'all')
            <div class="grid gap-5 lg:grid-cols-2">
                <div class="rounded-[28px] border border-white/10 bg-black/20 p-5">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                        <div class="relative mx-auto grid h-48 w-48 shrink-0 place-items-center rounded-full shadow-glow" style="background: conic-gradient(#8b5cf6 0 {{ $invitedPercent }}%, #34d399 {{ $invitedPercent }}% {{ $invitedPercent + $activatedPercent }}%, rgba(255,255,255,.08) {{ $invitedPercent + $activatedPercent }}% 100%);">
                            <div class="grid h-28 w-28 place-items-center rounded-full bg-night text-center">
                                <div>
                                    <p class="text-3xl font-black">{{ $totalInvited + $totalActivated }}</p>
                                    <p class="text-xs text-slate-400">tổng lượt</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 space-y-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[.18em] text-slate-500">Tổng lượt affiliate</p>
                                <h3 class="mt-2 text-2xl font-black">Đã mời & kích hoạt</h3>
                            </div>
                            <div class="grid gap-3">
                                <div class="rounded-2xl border border-white/10 bg-white/[.04] p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="flex items-center gap-2 text-sm text-slate-300"><span class="h-3 w-3 rounded-full bg-violet-400"></span>Đã mời</span>
                                        <span class="font-black">{{ $totalInvited }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $invitedPercent }}%</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[.04] p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="flex items-center gap-2 text-sm text-slate-300"><span class="h-3 w-3 rounded-full bg-emerald-400"></span>Đã kích hoạt</span>
                                        <span class="font-black">{{ $totalActivated }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $activatedPercent }}%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[28px] border border-white/10 bg-black/20 p-5">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                        <div class="relative mx-auto grid h-48 w-48 shrink-0 place-items-center rounded-full shadow-gold" style="background: conic-gradient(#f8c84e 0 {{ $affiliatePercent }}%, #38bdf8 {{ $affiliatePercent }}% {{ $affiliatePercent + $poolPercent }}%, rgba(255,255,255,.08) {{ $affiliatePercent + $poolPercent }}% 100%);">
                            <div class="grid h-28 w-28 place-items-center rounded-full bg-night text-center">
                                <div>
                                    <p class="text-2xl font-black">{{ number_format($affiliateIncomeVnd + $sharedPoolIncomeVnd, 0, ',', '.') }}</p>
                                    <p class="text-xs text-slate-400">đ</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 space-y-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[.18em] text-slate-500">Tổng thu nhập</p>
                                <h3 class="mt-2 text-2xl font-black">Affiliate & pool đồng chia</h3>
                            </div>
                            <div class="grid gap-3">
                                <div class="rounded-2xl border border-white/10 bg-white/[.04] p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="flex items-center gap-2 text-sm text-slate-300"><span class="h-3 w-3 rounded-full bg-gold"></span>Thu nhập affiliate</span>
                                        <span class="font-black">{{ number_format($affiliateIncomeVnd, 0, ',', '.') }} đ</span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $affiliatePercent }}%</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[.04] p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="flex items-center gap-2 text-sm text-slate-300"><span class="h-3 w-3 rounded-full bg-sky-400"></span>Pool đồng chia</span>
                                        <span class="font-black">{{ number_format($sharedPoolIncomeVnd, 0, ',', '.') }} đ</span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $poolPercent }}%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="grid min-h-[260px] grid-cols-5 items-end gap-4 rounded-[28px] border border-white/10 bg-black/20 p-5">
                @foreach($weeks as $week)
                    <div class="flex h-full flex-col justify-end gap-3">
                        <div class="flex h-48 items-end justify-center gap-2">
                            <div class="w-6 rounded-t-2xl bg-gradient-to-t from-violet-700 to-violet-300 shadow-glow transition hover:scale-105" style="height: {{ max(8, ($week['invited'] / $maxChartValue) * 100) }}%" title="Đã mời: {{ $week['invited'] }}"></div>
                            <div class="w-6 rounded-t-2xl bg-gradient-to-t from-emerald-700 to-emerald-300 shadow-lg shadow-emerald-950/40 transition hover:scale-105" style="height: {{ max(8, ($week['activated'] / $maxChartValue) * 100) }}%" title="Đã kích hoạt: {{ $week['activated'] }}"></div>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-bold text-white">{{ $week['label'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $week['invited'] }}/{{ $week['activated'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.1fr_.9fr]">
        <div class="glass rounded-[32px] p-6">
            <h2 class="text-2xl font-black">Thành viên đã giới thiệu</h2>
            <div class="mt-5 overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr><th class="py-3">Thành viên</th><th>Liên hệ</th><th>Ngày đăng ký</th><th>Trạng thái</th><th>Hạn gói</th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                    @forelse($referralRows as $item)
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4 font-bold text-white">{{ $item['user']?->name ?? 'Không xác định' }}</td>
                            <td>{{ $item['user']?->email }}<br><span class="text-xs text-slate-500">{{ $item['user']?->phone }}</span></td>
                            <td>{{ $item['referral']->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($item['is_active'])
                                    <span class="rounded-full bg-emerald-400/10 px-3 py-1 text-xs font-bold text-emerald-100">Đã active</span>
                                @else
                                    <span class="rounded-full bg-amber-300/10 px-3 py-1 text-xs font-bold text-amber-100">Chưa active</span>
                                @endif
                            </td>
                            <td>{{ $item['subscription_ends_at'] ? $item['subscription_ends_at']->format('d/m/Y') : 'Chưa có gói' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-slate-400">Chưa có thành viên affiliate trong bộ lọc này.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($referralRows->hasPages())
                <div class="mt-5 rounded-[24px] border border-white/10 bg-black/20 p-3">
                    {{ $referralRows->onEachSide(1)->links() }}
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="glass rounded-[32px] p-6">
                <h2 class="text-2xl font-black">{!! html_entity_decode('Ch&#7881;nh s&#7917;a th&#244;ng tin user') !!}</h2>
                <p class="mt-2 text-sm text-slate-400">{!! html_entity_decode('Admin c&#243; th&#7875; thay &#273;&#7893;i ID t&#224;i kho&#7843;n, Email, S&#7889; &#273;i&#7879;n tho&#7841;i v&#224; H&#7885; v&#224; t&#234;n c&#7911;a user ngay t&#7841;i &#273;&#226;y.') !!}</p>
                <form method="post" action="{{ route('admin.users.basic-info.update', $user) }}" class="mt-5 grid gap-4">
                    @csrf
                    @method('put')
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-sm text-slate-400">{!! html_entity_decode('ID t&#224;i kho&#7843;n') !!}</span>
                            <input class="premium-input" name="username" value="{{ old('username', $user->username) }}" required>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-sm text-slate-400">Email</span>
                            <input class="premium-input" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        </label>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-sm text-slate-400">{!! html_entity_decode('S&#7889; &#273;i&#7879;n tho&#7841;i') !!}</span>
                            <input class="premium-input" name="phone" value="{{ old('phone', $user->phone) }}" required>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-sm text-slate-400">{!! html_entity_decode('H&#7885; v&#224; t&#234;n') !!}</span>
                            <input class="premium-input" name="name" value="{{ old('name', $user->name) }}" required>
                        </label>
                    </div>
                    <button class="w-full rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                        {!! html_entity_decode('L&#432;u th&#244;ng tin user') !!}
                    </button>
                </form>
            </div>

            <div class="glass rounded-[32px] p-6">
                <h2 class="text-2xl font-black">Thu nhập 3 tháng gần đây</h2>
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[360px] text-left text-sm">
                        <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                        <tr><th class="py-3">Tháng</th><th>Tổng</th></tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                        @forelse($ordersByMonth as $row)
                            <tr class="text-slate-300">
                                <td class="py-4">{{ $row->month }}</td>
                                <td>{{ number_format($row->total, 0, ',', '.') }} đ</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-8 text-center text-slate-400">Chưa có đơn thanh toán.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass rounded-[32px] p-6">
                <h2 class="text-2xl font-black">Kiểm soát tài khoản</h2>
                @if($activeSuspension)
                    <div class="mt-4 rounded-[24px] border border-rose-300/20 bg-rose-500/10 p-4 text-sm leading-6 text-rose-100">
                        <p class="font-bold">Đang khóa {{ $activeSuspension->type === 'permanent' ? 'vĩnh viễn' : 'tạm thời' }}</p>
                        <p>Lý do: {{ $activeSuspension->reason }}</p>
                        @if($activeSuspension->ends_at)
                            <p>Hết hạn: {{ $activeSuspension->ends_at->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                @else
                    <div class="mt-4 rounded-[24px] border border-emerald-300/20 bg-emerald-500/10 p-4 text-sm leading-6 text-emerald-100">
                        Tài khoản đang hoạt động, chưa có khóa tạm thời hoặc vĩnh viễn.
                    </div>
                @endif
                <form method="post" action="{{ route('admin.users.suspend', $user) }}" class="mt-5 space-y-4">
                    @csrf
                    <select class="premium-input" name="type">
                        <option value="temporary">Tạm thời</option>
                        <option value="permanent">Vĩnh viễn</option>
                    </select>
                    <input class="premium-input" name="reason" placeholder="Lý do khóa" required>
                    <button class="w-full rounded-2xl bg-rose-500/90 px-5 py-4 font-black transition hover:-translate-y-1 hover:bg-rose-400">Khóa tài khoản</button>
                </form>
                <form method="post" action="{{ route('admin.users.unlock', $user) }}" class="mt-4">
                    @csrf
                    <button class="w-full rounded-2xl px-5 py-4 font-black transition {{ $activeSuspension ? 'bg-emerald-500/90 hover:-translate-y-1 hover:bg-emerald-400' : 'cursor-not-allowed border border-white/10 bg-white/10 text-slate-400' }}" @disabled(!$activeSuspension)>Mở khóa tài khoản</button>
                </form>
                <form method="post" action="{{ route('admin.users.unlock-bank', $user) }}" class="mt-4">
                    @csrf
                    <button class="w-full rounded-2xl border border-white/10 bg-white/10 px-5 py-4 font-bold transition hover:bg-white/15">Mở quyền sửa STK</button>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection
