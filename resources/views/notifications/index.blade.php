@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(248,200,78,.22),transparent_30%),radial-gradient(circle_at_18%_72%,rgba(14,165,233,.25),transparent_30%)]"></div>
        <div class="relative flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-amber-200/80">Notifications</p>
                <h1 class="mt-3 text-4xl font-black sm:text-6xl">Thông báo</h1>
                <p class="mt-4 max-w-2xl text-slate-300">Lịch sử thông báo hệ thống, trạng thái đã đọc và nội dung chi tiết.</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 px-5 py-4 text-center">
                <p class="text-xs text-slate-400">Chưa đọc</p>
                <p class="mt-1 text-2xl font-black">{{ $announcementRows->where('is_read', false)->count() }}</p>
            </div>
        </div>
    </section>

    <section class="glass min-w-0 rounded-[24px] p-4 sm:rounded-[32px] sm:p-6" x-data="{ announcementDetail: null }">
        <div class="mb-5 flex min-w-0 flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div class="min-w-0">
                <p class="mobile-wrap text-sm font-semibold uppercase tracking-[.14em] text-amber-200/70 sm:tracking-[.24em]">History</p>
                <h2 class="break-anywhere mt-2 text-2xl font-black sm:text-3xl">Thông báo gần đây</h2>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-2 text-sm text-slate-300">
                {{ $announcementRows->count() }} thông báo
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                <tr>
                    <th class="py-3">Số thông báo</th>
                    <th>Ngày thông báo</th>
                    <th>Nội dung text tóm tắt</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                @forelse($announcementRows as $announcement)
                    <tr class="text-slate-300 transition hover:bg-white/[.04]">
                        <td class="py-4 font-semibold text-white">{{ $announcement['number'] }}</td>
                        <td>{{ $announcement['date_label'] }}</td>
                        <td class="max-w-md">{{ $announcement['summary'] }}</td>
                        <td>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $announcement['is_read'] ? 'bg-emerald-400/10 text-emerald-100' : 'bg-amber-300/10 text-amber-100' }}">
                                {{ $announcement['status_label'] }}
                            </span>
                        </td>
                        <td>
                            <button type="button" class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-bold text-violet-100 transition hover:bg-violet-400/20" @click="announcementDetail = {{ \Illuminate\Support\Js::from($announcement) }}">
                                Xem chi tiết
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center text-slate-400">Chưa có thông báo.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="announcementDetail" x-cloak x-transition.opacity class="fixed inset-0 z-[90] grid place-items-center bg-black/75 p-4 backdrop-blur-xl" @click.self="announcementDetail = null">
            <div x-transition.scale class="glass max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-[24px] p-4 sm:rounded-[32px] sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/80" x-text="announcementDetail?.type_label"></p>
                        <h3 class="break-anywhere mt-2 text-2xl font-black" x-text="announcementDetail?.title"></h3>
                        <p class="mt-1 text-xs text-slate-400" x-text="announcementDetail?.created_at_label"></p>
                    </div>
                    <button type="button" class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white/10 transition hover:bg-white/20" @click="announcementDetail = null">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <template x-if="announcementDetail?.image_url">
                    <img class="mt-5 max-h-[360px] w-full rounded-[24px] object-cover" :src="announcementDetail?.image_url" :alt="announcementDetail?.title" draggable="false">
                </template>
                <p class="mt-5 whitespace-pre-line text-sm leading-7 text-slate-200" x-text="announcementDetail?.body"></p>
                <form method="post" :action="announcementDetail?.read_url" class="mt-6" x-show="announcementDetail && !announcementDetail.is_read">
                    @csrf
                    <button class="w-full rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-3 font-black text-white shadow-glow transition hover:-translate-y-1">
                        Xác nhận đã đọc
                    </button>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection
