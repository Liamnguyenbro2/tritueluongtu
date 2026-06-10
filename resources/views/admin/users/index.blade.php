@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6 sm:p-8">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">User Control</p>
                <h1 class="mt-3 text-4xl font-black sm:text-5xl">Quản trị user</h1>
                <p class="mt-3 max-w-3xl text-slate-400">Danh sách user được sắp xếp theo tài khoản tạo mới nhất, hiển thị số dư ví, gói kích hoạt và đường dẫn đi tới lịch sử giao dịch chi tiết.</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 px-5 py-4">
                <p class="text-sm text-slate-400">Tổng user thường</p>
                <p class="mt-2 text-3xl font-black">{{ $stats['total'] }}</p>
            </div>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-[24px] border border-white/10 bg-black/20 px-5 py-4">
                <p class="text-sm text-slate-400">Ch&#432;a k&iacute;ch ho&#7841;t</p>
                <p class="mt-2 text-3xl font-black">{{ $stats['inactive'] }}</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/20 px-5 py-4">
                <p class="text-sm text-slate-400">User g&oacute;i th&aacute;ng</p>
                <p class="mt-2 text-3xl font-black">{{ $stats['monthly'] }}</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/20 px-5 py-4">
                <p class="text-sm text-slate-400">User g&oacute;i n&#259;m</p>
                <p class="mt-2 text-3xl font-black">{{ $stats['yearly'] }}</p>
            </div>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-5 flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <h2 class="text-2xl font-black">Danh sách user</h2>
                <p class="mt-1 text-sm text-slate-400">Tìm theo Email hoặc ID tài khoản.</p>
            </div>
            <form method="get" action="{{ route('admin.users.index') }}" class="flex w-full gap-2 lg:w-[460px]">
                <div class="relative flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                    <input class="premium-input pl-11" name="q" value="{{ $search }}" placeholder="Email hoặc ID tài khoản">
                </div>
                <button class="rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-3 font-bold shadow-glow transition hover:-translate-y-0.5">Tìm</button>
                @if($search !== '')
                    <a href="{{ route('admin.users.index') }}" class="grid place-items-center rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-bold text-slate-200 transition hover:bg-white/15">Xóa</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr>
                        <th class="py-3">Số thứ tự</th>
                        <th>ID tài khoản</th>
                        <th>Email</th>
                        <th>Ví số dư</th>
                        <th>Gói kích hoạt</th>
                        <th>Ngày tạo tài khoản</th>
                        <th>Xem thêm</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($users as $index => $user)
                        @php
                            $rowNumber = ($users->firstItem() ?? 1) + $index;
                            $activeSubscription = $user->subscriptions->first();
                            $planLabel = match ($activeSubscription?->plan?->code) {
                                'monthly' => 'Gói tháng',
                                'yearly' => 'Gói năm',
                                default => 'Chưa kích hoạt',
                            };
                            $planClass = match ($activeSubscription?->plan?->code) {
                                'monthly' => 'bg-amber-300/10 text-amber-100',
                                'yearly' => 'bg-emerald-400/10 text-emerald-100',
                                default => 'bg-white/10 text-slate-200',
                            };
                        @endphp
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4 font-semibold text-white">{{ $rowNumber }}</td>
                            <td class="font-bold text-white">{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ number_format($user->wallet?->balance_vnd ?? 0, 0, ',', '.') }} đ</td>
                            <td>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $planClass }}">
                                    {{ $planLabel }}
                                </span>
                            </td>
                            <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center gap-2 rounded-2xl bg-violet-400/10 px-4 py-2 text-sm font-bold text-violet-100 transition hover:bg-violet-400/20">
                                    Xem thêm
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400">Chưa có user thường nào trong hệ thống.</td>
                        </tr>
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
