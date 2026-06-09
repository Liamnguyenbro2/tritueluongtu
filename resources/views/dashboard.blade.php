@extends('layouts.app')

@section('content')
@php
    $thumbs = [
        'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1490750967868-88aa4486c946?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1518495973542-4542c06a5843?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=900&q=80',
    ];
    $unlocked = $lessons->where('locked', false)->count();
    $locked = $lessons->where('locked', true)->count();
@endphp

<div
    x-data="{
        preview: null,
        forcedAnnouncement: {{ \Illuminate\Support\Js::from(($pendingAnnouncements ?? collect())->first()) }},
        previewAutoLoop: false,
        previewFullscreen: false,
        init() {
            document.addEventListener('fullscreenchange', () => this.handleFullscreenChange());
        },
        setPreview(lesson) {
            this.preview = lesson;
            this.previewFullscreen = false;
            this.$nextTick(() => this.syncPreviewVideo());
        },
        closePreview() {
            const video = this.$refs.previewVideo;
            if (video) {
                video.pause();
                video.loop = false;
            }

            this.preview = null;
            this.previewFullscreen = false;
        },
        togglePreviewAutoLoop() {
            this.previewAutoLoop = !this.previewAutoLoop;
            this.syncPreviewVideo();
        },
        handleFullscreenChange() {
            const fullscreenElement = document.fullscreenElement;
            const video = this.$refs.previewVideo;
            this.previewFullscreen = !!(fullscreenElement && video && (fullscreenElement === video || fullscreenElement.contains(video)));
            this.syncPreviewVideo();
        },
        syncPreviewVideo() {
            const video = this.$refs.previewVideo;
            if (!video) {
                return;
            }

            video.loop = this.previewAutoLoop && this.previewFullscreen;
        },
    }"
    class="max-w-full overflow-x-hidden space-y-6 sm:space-y-8"
>
    <div x-show="forcedAnnouncement" x-cloak x-transition.opacity class="fixed inset-0 z-[100] grid place-items-center bg-black/80 p-4 backdrop-blur-xl" @click.self="forcedAnnouncement = null">
        <div x-transition.scale class="glass max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-[24px] p-4 sm:rounded-[32px] sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/80" x-text="forcedAnnouncement?.type_label"></p>
                    <h3 class="break-anywhere mt-2 text-2xl font-black" x-text="forcedAnnouncement?.title"></h3>
                    <p class="mt-1 text-xs text-slate-400" x-text="forcedAnnouncement?.created_at_label"></p>
                </div>
                <button type="button" class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white/10 transition hover:bg-white/20" @click="forcedAnnouncement = null">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <template x-if="forcedAnnouncement?.image_url">
                <img class="mt-5 max-h-[360px] w-full rounded-[24px] object-cover" :src="forcedAnnouncement?.image_url" :alt="forcedAnnouncement?.title" draggable="false">
            </template>
            <p class="mt-5 whitespace-pre-line text-sm leading-7 text-slate-200" x-text="forcedAnnouncement?.body"></p>
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <button type="button" class="rounded-2xl border border-white/10 bg-white/10 px-5 py-3 font-bold text-slate-100 transition hover:bg-white/15" @click="forcedAnnouncement = null">
                    Bỏ qua
                </button>
                <form method="post" :action="forcedAnnouncement?.read_url">
                    @csrf
                    <button class="w-full rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-3 font-black text-white shadow-glow transition hover:-translate-y-1">
                        Xác nhận đã đọc
                    </button>
                </form>
            </div>
        </div>
    </div>

    <section class="relative max-w-full overflow-hidden rounded-[24px] border border-white/10 bg-white/[.06] p-4 shadow-glow backdrop-blur-2xl sm:rounded-[32px] sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_15%,rgba(139,92,246,.45),transparent_30%),radial-gradient(circle_at_22%_85%,rgba(248,200,78,.18),transparent_30%)]"></div>
        <div class="relative grid min-w-0 gap-6 sm:gap-8 xl:grid-cols-[1.25fr_.75fr]">
            <div class="min-w-0">
                <div class="mobile-wrap mb-5 inline-flex max-w-full items-center gap-2 rounded-full border border-violet-300/20 bg-violet-400/10 px-3 py-2 text-[11px] font-semibold uppercase tracking-[.14em] text-violet-100 sm:px-4 sm:text-xs sm:tracking-[.24em]">
                    <i data-lucide="sparkles" class="h-4 w-4"></i> Trí tuệ lượng tử
                </div>
                <p class="text-sm text-slate-300">Xin chào,</p>
                <h1 class="break-anywhere mt-1 text-3xl font-black tracking-tight text-white sm:text-6xl">{{ auth()->user()->name }}</h1>
                <div class="mt-4 inline-flex max-w-full items-center gap-2 rounded-2xl border border-violet-300/20 bg-violet-400/10 px-3 py-2 text-sm font-semibold text-violet-100/90 shadow-[0_0_24px_rgba(139,92,246,.14)] sm:mt-5 sm:px-4">
                    <span class="text-[10px] uppercase tracking-[.22em] text-violet-200/70 sm:text-xs">ID tài khoản</span>
                    <span class="break-anywhere text-sm text-white/95 sm:text-base">{{ auth()->user()->username }}</span>
                </div>
                <p class="mt-5 max-w-2xl text-base leading-8 text-slate-300">Hệ thống sử dụng trải nghiệm hình ảnh và âm thanh mô phỏng trạng thái.</p>
                <div class="mt-7 flex min-w-0 flex-row gap-3 sm:flex-wrap">
                    <a href="{{ route('billing') }}" class="group inline-flex min-w-0 flex-1 items-center justify-center gap-1.5 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-3 py-3 text-xs font-bold text-white shadow-glow transition hover:-translate-y-1 hover:shadow-[0_0_56px_rgba(139,92,246,.55)] sm:flex-none sm:gap-2 sm:px-5 sm:text-sm">
                        <i data-lucide="crown" class="h-5 w-5"></i> Nâng cấp trải nghiệm
                    </a>
                    <a href="{{ route('wallet') }}" class="inline-flex min-w-0 flex-1 items-center justify-center gap-1.5 rounded-2xl border border-white/10 bg-white/10 px-3 py-3 text-xs font-semibold text-white transition hover:-translate-y-1 hover:bg-white/15 sm:flex-none sm:gap-2 sm:px-5 sm:text-sm">
                        <i data-lucide="wallet" class="h-5 w-5"></i> Ví số dư
                    </a>
                </div>
            </div>

            <div class="grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-1">
                <div class="glass min-w-0 rounded-[24px] p-4 sm:rounded-[28px] sm:p-5">
                    <p class="text-sm text-slate-400">Số dư tài khoản</p>
                    <p class="break-anywhere mt-2 text-3xl font-black">{{ number_format(auth()->user()->wallet?->balance_vnd ?? 0, 0, ',', '.') }} đ</p>
                    <div class="mt-4 h-2 rounded-full bg-white/10">
                        <div class="h-2 w-3/5 rounded-full bg-gradient-to-r from-violet-400 to-amber-300 shadow-glow"></div>
                    </div>
                </div>
                <div class="glass min-w-0 rounded-[24px] p-4 sm:rounded-[28px] sm:p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-slate-400">Thời hạn gói</p>
                        <i data-lucide="timer" class="h-5 w-5 text-amber-200"></i>
                    </div>
                    @if($activeSubscription && ! ($hasPerLessonMonthlyUnlocks && $activeSubscription->plan?->code === config('quantum.plans.monthly_code') && ! ($activeSubscription->grants_full_library ?? false)))
                        <div x-data="subscriptionCountdown('{{ $activeSubscription->ends_at->toIso8601String() }}')" class="mt-3 rounded-2xl border border-amber-200/20 bg-amber-300/10 px-4 py-3">
                            <p class="text-sm font-semibold text-amber-100">{{ $activeSubscription->plan?->name }} của bạn còn lại</p>
                            <p class="break-anywhere mt-2 font-mono text-xl font-black text-white sm:text-2xl">
                                <span x-text="days"></span> ngày | <span x-text="time"></span>
                            </p>
                            <p class="mt-1 text-xs text-slate-300">Hết hạn: {{ $activeSubscription->ends_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @elseif($hasPerLessonMonthlyUnlocks)
                        <div class="mt-3 rounded-2xl border border-violet-300/20 bg-violet-400/10 px-4 py-3">
                            <p class="text-sm font-semibold text-violet-100">G&#243;i th&#225;ng theo t&#7915;ng kh&#243;a &#273;ang ho&#7841;t &#273;&#7897;ng</p>
                            <p class="mt-2 text-sm leading-6 text-slate-200">M&#7895;i b&#224;i h&#7885;c &#273;&#227; k&#237;ch ho&#7841;t c&#243; &#273;&#7891;ng h&#7891; 30 ng&#224;y ri&#234;ng ngay tr&#234;n card c&#7911;a kh&#243;a &#273;&#243;.</p>
                            <p class="mt-1 text-xs text-slate-400">C&#225;c b&#224;i ch&#432;a k&#237;ch ho&#7841;t s&#7869; hi&#7875;n n&#250;t n&#226;ng c&#7845;p thay v&#236; &#273;&#7891;ng h&#7891;.</p>
                        </div>
                    @else
                        <div class="mt-3 rounded-2xl border border-white/10 bg-black/20 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-200">Bạn chưa có gói trả phí đang hoạt động.</p>
                            <p class="mt-1 text-xs text-slate-400">Nâng cấp ngay để mở khóa thư viện và quyền sử dụng bài học.</p>
                        </div>
                    @endif
                    <a href="{{ route('billing') }}" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-4 py-3 text-sm font-black text-white shadow-glow transition hover:-translate-y-1">
                        <i data-lucide="crown" class="h-4 w-4"></i> Nâng cấp
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="grid min-w-0 grid-cols-2 gap-4 md:grid-cols-3">
        <div class="glass min-w-0 rounded-[24px] p-4 transition hover:-translate-y-1 hover:shadow-glow sm:rounded-[28px] sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-400">Đang mở</p>
                <i data-lucide="unlock" class="h-5 w-5 text-emerald-300"></i>
            </div>
            <p class="mt-3 text-4xl font-black">{{ $unlocked }}</p>
        </div>
        <div class="glass min-w-0 rounded-[24px] p-4 transition hover:-translate-y-1 hover:shadow-glow sm:rounded-[28px] sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-400">Đã khóa</p>
                <i data-lucide="lock" class="h-5 w-5 text-rose-300"></i>
            </div>
            <p class="mt-3 text-4xl font-black">{{ $locked }}</p>
        </div>
        <div class="glass col-span-2 min-w-0 rounded-[24px] p-4 transition hover:-translate-y-1 hover:shadow-glow sm:rounded-[28px] sm:p-5 md:col-span-1" x-data="{ copied: false, link: '{{ url('/register?ref='.auth()->user()->username) }}' }">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-400">Link giới thiệu</p>
                <i data-lucide="link" class="h-5 w-5 text-violet-300"></i>
            </div>
            <div class="mt-3 flex min-w-0 flex-col gap-2 rounded-2xl bg-black/20 p-2 sm:flex-row sm:items-center">
                <p class="min-w-0 flex-1 truncate px-2 py-2 text-sm text-slate-300 sm:py-0" x-text="link"></p>
                <button type="button" class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-bold text-violet-100 transition hover:-translate-y-0.5 hover:bg-violet-400/20 hover:shadow-glow sm:w-auto" @click="navigator.clipboard.writeText(link).then(() => { copied = true; setTimeout(() => copied = false, 1600) })">
                    <i x-show="!copied" data-lucide="copy" class="h-4 w-4"></i>
                    <i x-show="copied" data-lucide="check" class="h-4 w-4 text-emerald-200"></i>
                    <span x-text="copied ? 'Đã sao chép' : 'Sao chép'"></span>
                </button>
            </div>
        </div>
    </section>

    <section class="min-w-0">
        <div class="mb-5 flex min-w-0 flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div class="min-w-0">
                <p class="mobile-wrap text-sm font-semibold uppercase tracking-[.14em] text-violet-200/70 sm:tracking-[.24em]">Trí tuệ lượng tử Album</p>
                <h2 class="break-anywhere mt-2 text-3xl font-black sm:text-4xl">Thư viện nội dung ({{ $lessons->count() }})</h2>
            </div>
            <div class="break-anywhere w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-center text-sm text-slate-300 sm:w-auto sm:rounded-full sm:text-left">Bản quyền bởi Trí tuệ lượng tử ®</div>
        </div>

        <div class="grid min-w-0 grid-cols-2 gap-3 sm:gap-5 xl:grid-cols-4">
            @foreach($lessons as $lesson)
                @php $thumb = $lesson['thumbnail_url'] ?: $thumbs[($lesson['position'] - 1) % count($thumbs)]; @endphp
                <article class="group min-w-0 overflow-hidden rounded-[18px] border border-white/10 bg-white/[.06] shadow-2xl shadow-black/30 backdrop-blur-xl transition duration-300 hover:-translate-y-2 hover:border-violet-300/40 hover:shadow-glow sm:rounded-[28px]">
                    <button type="button" class="block w-full text-left" @click='setPreview(@json($lesson))'>
                        <div class="relative aspect-[4/3] overflow-hidden sm:aspect-[16/10]">
                            <img src="{{ $thumb }}" alt="{{ $lesson['title'] }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-110" draggable="false">
                            <div class="absolute inset-0 bg-gradient-to-t from-night via-night/30 to-transparent"></div>
                            <div class="absolute left-2 top-2 rounded-xl border border-white/20 bg-black/35 px-2 py-1 text-xs font-bold backdrop-blur-xl sm:left-4 sm:top-4 sm:rounded-2xl sm:px-3 sm:text-sm">{{ str_pad($lesson['position'], 2, '0', STR_PAD_LEFT) }}</div>
                            @if($lesson['locked'])
                                <div class="absolute right-2 top-2 grid h-8 w-8 place-items-center rounded-xl bg-black/45 backdrop-blur-xl sm:right-4 sm:top-4 sm:h-10 sm:w-10 sm:rounded-2xl"><i data-lucide="lock" class="h-4 w-4 text-white sm:h-5 sm:w-5"></i></div>
                            @endif
                        </div>
                    </button>

                    <div class="min-w-0 p-3 sm:p-5">
                        <div class="mb-2 flex flex-wrap items-center gap-1.5 sm:mb-3 sm:gap-2">
                            <div class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-[10px] font-semibold sm:gap-2 sm:px-3 sm:text-xs {{ $lesson['locked'] ? 'bg-rose-400/10 text-rose-100' : 'bg-emerald-400/10 text-emerald-100' }}">
                                <span class="h-1.5 w-1.5 rounded-full sm:h-2 sm:w-2 {{ $lesson['locked'] ? 'bg-rose-300' : 'bg-emerald-300' }}"></span>
                                {{ $lesson['locked'] ? 'Đã khóa' : 'Đang chạy' }}
                            </div>
                            @if($lesson['trial'])
                                <div class="inline-flex items-center gap-1.5 rounded-full border border-amber-200/20 bg-amber-300/10 px-2 py-1 text-[10px] font-bold text-amber-100 sm:gap-2 sm:px-3 sm:text-xs">
                                    <i data-lucide="clock-3" class="h-3 w-3 sm:h-3.5 sm:w-3.5"></i>
                                    Trải nghiệm
                                </div>
                            @elseif($lesson['is_unlocked_lesson'] ?? false)
                                <div class="inline-flex items-center gap-1.5 rounded-full border border-violet-200/20 bg-violet-400/10 px-2 py-1 text-[10px] font-bold text-violet-100 sm:gap-2 sm:px-3 sm:text-xs">
                                    <i data-lucide="badge-check" class="h-3 w-3 sm:h-3.5 sm:w-3.5"></i>
                                    Đã mở khóa
                                </div>
                            @endif
                        </div>

                        <h3 class="break-anywhere text-sm font-bold leading-5 sm:text-lg sm:leading-7">{{ $lesson['title'] }}</h3>
                        <p class="mt-1 text-xs text-slate-400 sm:mt-2 sm:text-sm">{{ $lesson['trial'] ? 'Trải nghiệm miễn phí' : 'Nội dung trả phí' }}</p>

                        @if($lesson['expires_at'])
                            <div x-data="{{ $lesson['trial'] ? "countdown('{$lesson['expires_at']}')" : "lessonCountdown('{$lesson['expires_at']}')" }}" class="mt-3 rounded-2xl border border-white/10 bg-black/20 px-3 py-3 sm:mt-4 sm:px-4">
                                <div class="flex min-w-0 flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between sm:gap-2">
                                    <span class="text-[10px] font-semibold uppercase text-slate-400 sm:text-xs">{{ $lesson['trial'] ? 'Còn lại trial' : 'Hệ thống tự động tắt' }}</span>
                                    @if($lesson['trial'])
                                        <span class="font-mono text-xs font-black text-amber-100 sm:text-sm" x-text="remaining"></span>
                                    @else
                                        <span class="break-anywhere font-mono text-xs font-black text-amber-100 sm:text-sm"><span x-text="days"></span> ngày | <span x-text="time"></span></span>
                                    @endif
                                </div>
                                <p class="mt-1 text-[10px] text-slate-500 sm:text-xs">Hết hạn: {{ $lesson['expires_label'] }}</p>
                            </div>
                        @endif

                        @if($lesson['membership_expires_at'])
                            <div x-data="lessonCountdown('{{ $lesson['membership_expires_at'] }}')" class="mt-3 rounded-2xl border border-violet-300/15 bg-violet-400/10 px-3 py-3 sm:px-4">
                                <div class="flex min-w-0 flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between sm:gap-2">
                                    <span class="text-[10px] font-semibold uppercase text-violet-100/80 sm:text-xs">GÓI THÁNG CÒN LẠI</span>
                                    <span class="break-anywhere font-mono text-xs font-black text-violet-100 sm:text-sm"><span x-text="days"></span> ngày | <span x-text="time"></span></span>
                                </div>
                                <p class="mt-1 text-[10px] text-violet-100/60 sm:text-xs">Hết hạn: {{ $lesson['membership_expires_label'] }}</p>
                            </div>
                        @elseif($lesson['membership_expired'] && $lesson['requires_membership_upgrade'])
                            <div class="mt-3 rounded-2xl border border-rose-300/15 bg-rose-400/10 px-3 py-3 sm:px-4">
                                <div class="flex min-w-0 items-center justify-between gap-2">
                                    <span class="text-[10px] font-semibold uppercase text-rose-100/80 sm:text-xs">GÓI THÁNG ĐÃ HẾT HẠN</span>
                                    <span class="text-xs font-black text-rose-100 sm:text-sm">Đã hết hạn</span>
                                </div>
                                <p class="mt-1 text-[10px] text-rose-100/60 sm:text-xs">Hãy nâng cấp lại để tiếp tục mở khóa bài học.</p>
                            </div>
                        @endif

                        <div class="mt-3 flex min-w-0 items-center justify-between gap-2 sm:mt-5 sm:gap-3">
                            @if($lesson['trial'])
                                <button disabled class="flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-2xl border {{ $lesson['locked'] ? 'border-rose-300/20 bg-rose-400/10 text-rose-100' : 'border-emerald-300/20 bg-emerald-400/10 text-emerald-100' }} px-3 py-3 text-xs font-bold sm:gap-3 sm:px-4 sm:text-sm">
                                    <span class="relative inline-flex h-5 w-9 items-center rounded-full sm:h-6 sm:w-11 {{ $lesson['locked'] ? 'bg-rose-400/25' : 'bg-emerald-400/30' }}">
                                        <span class="{{ $lesson['locked'] ? 'ml-1 bg-rose-200' : 'ml-4 sm:ml-5 bg-emerald-200' }} h-4 w-4 rounded-full shadow-lg sm:h-5 sm:w-5"></span>
                                    </span>
                                    {{ $lesson['locked'] ? 'Hết trial' : 'Đang bật' }}
                                </button>
                            @elseif($lesson['can_unlock'])
                                <form class="flex-1" method="post" action="{{ route('lessons.unlock', $lesson['id']) }}">
                                    @csrf
                                    <button class="inline-flex w-full items-center justify-center gap-1.5 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-3 py-3 text-xs font-bold transition hover:shadow-glow sm:gap-2 sm:px-4 sm:text-sm">
                                        <i data-lucide="wallet" class="h-3.5 w-3.5 sm:h-4 sm:w-4"></i>
                                        Mở khóa {{ number_format($lesson['unlock_price_vnd'], 0, ',', '.') }} đ
                                    </button>
                                </form>
                            @elseif($lesson['can_activate'])
                                <form class="flex-1" method="post" action="{{ route('lessons.toggle', $lesson['id']) }}">
                                    @csrf
                                    <button class="flex w-full items-center justify-center gap-2 rounded-2xl border {{ $lesson['active'] ? 'border-emerald-300/20 bg-emerald-400/10 text-emerald-100 hover:bg-emerald-400/15' : 'border-slate-300/15 bg-white/5 text-slate-100 hover:bg-white/10' }} px-3 py-3 text-xs font-bold transition sm:gap-3 sm:px-4 sm:text-sm">
                                        <span class="relative inline-flex h-5 w-9 items-center rounded-full sm:h-6 sm:w-11 {{ $lesson['active'] ? 'bg-emerald-400/30' : 'bg-slate-500/30' }}">
                                            <span class="{{ $lesson['active'] ? 'ml-4 sm:ml-5 bg-emerald-200' : 'ml-1 bg-slate-300' }} h-4 w-4 rounded-full shadow-lg sm:h-5 sm:w-5"></span>
                                        </span>
                                        {{ $lesson['active'] ? 'Đang bật' : 'Đang tắt' }}
                                    </button>
                                </form>
                            @elseif($lesson['requires_membership_upgrade'])
                                <a class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-3 py-3 text-xs font-bold transition hover:shadow-glow sm:gap-2 sm:px-4 sm:text-sm" href="{{ route('billing') }}">
                                    <i data-lucide="zap" class="h-3.5 w-3.5 sm:h-4 sm:w-4"></i> Nâng cấp
                                </a>
                            @else
                                <button disabled class="flex w-full cursor-default items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-3 py-3 text-xs font-bold text-slate-100 sm:gap-3 sm:px-4 sm:text-sm">
                                    <i data-lucide="shield-check" class="h-4 w-4"></i> Toàn quyền truy cập
                                </button>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <div x-show="preview" x-cloak x-transition.opacity class="fixed inset-0 z-[80] grid place-items-center bg-black/75 p-4 backdrop-blur-xl" @click.self="closePreview()">
        <div x-transition.scale class="glass w-full max-w-lg rounded-[24px] p-4 sm:rounded-[32px] sm:p-6">
            <div class="flex items-start justify-between gap-4 sm:gap-6">
                <div class="min-w-0">
                    <p class="text-sm text-violet-200">Bạn đang xem trước nội dung</p>
                    <h3 class="break-anywhere mt-2 text-2xl font-black" x-text="preview?.title"></h3>
                </div>
                <button class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white/10 transition hover:bg-white/20" @click="closePreview()">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <div class="mt-5 overflow-hidden rounded-[24px] border border-white/10 bg-black/25" x-show="preview?.media_url">
                <template x-if="preview?.media_type === 'video'">
                    <video x-ref="previewVideo" class="max-h-[360px] w-full object-contain" controls controlsList="nodownload noplaybackrate" disablepictureinpicture oncontextmenu="return false" :src="preview?.media_url" @loadedmetadata="syncPreviewVideo()"></video>
                </template>
                <template x-if="preview?.media_type === 'embed-video'">
                    <iframe
                        class="aspect-video w-full rounded-[24px] bg-black"
                        :src="preview?.media_url"
                        allow="autoplay; fullscreen; picture-in-picture"
                        allowfullscreen
                        referrerpolicy="strict-origin-when-cross-origin"
                    ></iframe>
                </template>
                <template x-if="preview?.media_type !== 'video' && preview?.media_type !== 'embed-video'">
                    <img class="max-h-[360px] w-full object-cover" :src="preview?.media_url" :alt="preview?.title" draggable="false">
                </template>
            </div>
            <div x-show="preview?.media_type === 'video'" class="mt-4 rounded-[24px] border border-white/10 bg-black/25 p-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-100">Tự động lặp lại khi toàn màn hình</p>
                        <p class="mt-1 text-xs text-slate-400">Bật tùy chọn này để video tự phát lại khi khách đang xem fullscreen.</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center gap-3 rounded-2xl border px-3 py-2 text-sm font-bold transition"
                        :class="previewAutoLoop ? 'border-emerald-300/30 bg-emerald-400/10 text-emerald-100' : 'border-white/10 bg-white/5 text-slate-200'"
                        @click="togglePreviewAutoLoop()"
                    >
                        <span class="relative inline-flex h-6 w-11 items-center rounded-full transition" :class="previewAutoLoop ? 'bg-emerald-400/30' : 'bg-slate-500/30'">
                            <span class="h-5 w-5 rounded-full shadow-lg transition" :class="previewAutoLoop ? 'ml-5 bg-emerald-200' : 'ml-1 bg-slate-300'"></span>
                        </span>
                        <span x-text="previewAutoLoop ? 'Đã bật' : 'Đang tắt'"></span>
                    </button>
                </div>
                <p class="mt-3 text-xs" :class="previewFullscreen ? 'text-emerald-200' : 'text-slate-500'">
                    <span x-text="previewFullscreen ? 'Chế độ toàn màn hình đang mở, video sẽ lặp lại theo lựa chọn của bạn.' : 'Tùy chọn này sẽ kích hoạt khi người xem phóng to video toàn màn hình.'"></span>
                </p>
            </div>
            <p class="mt-4 text-sm text-slate-400" x-show="preview?.description" x-text="preview?.description"></p>
            <div class="mt-6 rounded-[24px] border border-white/10 bg-black/25 p-4">
                <p class="text-sm text-slate-400">Trạng thái</p>
                <p class="mt-1 font-semibold" x-text="preview?.locked ? 'Đang khóa' : 'Sẵn sàng trải nghiệm'"></p>
            </div>
        </div>
    </div>
</div>
@endsection
