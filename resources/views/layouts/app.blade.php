<!doctype html>
<html lang="vi" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Năng Lượng Tích Cực') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        night: '#060711',
                        ink: '#0c1020',
                        violetNeon: '#8b5cf6',
                        gold: '#f8c84e'
                    },
                    boxShadow: {
                        glow: '0 0 40px rgba(139,92,246,.34)',
                        gold: '0 0 34px rgba(248,200,78,.28)'
                    }
                }
            }
        }
    </script>
    <script defer src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body {
            background:
                radial-gradient(circle at 18% 8%, rgba(124,58,237,.28), transparent 28%),
                radial-gradient(circle at 82% 14%, rgba(248,200,78,.16), transparent 24%),
                radial-gradient(circle at 50% 100%, rgba(14,165,233,.12), transparent 32%),
                #060711;
        }
        html, body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
        *, *::before, *::after {
            box-sizing: border-box;
        }
        img, video, canvas, svg {
            max-width: 100%;
        }
        svg {
            flex-shrink: 0;
        }
        main, section, article, form, .grid, .flex {
            min-width: 0;
        }
        .grid > *, .flex > * {
            min-width: 0;
        }
        .glass {
            background: linear-gradient(145deg, rgba(255,255,255,.10), rgba(255,255,255,.035));
            border: 1px solid rgba(255,255,255,.12);
            box-shadow: 0 24px 80px rgba(0,0,0,.35);
            backdrop-filter: blur(24px);
        }
        .premium-input {
            width: 100%;
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(6,7,17,.72);
            padding: 13px 15px;
            color: white;
            outline: none;
            transition: .2s ease;
        }
        .premium-input:focus {
            border-color: rgba(139,92,246,.8);
            box-shadow: 0 0 0 4px rgba(139,92,246,.16);
        }
        body, img, video, a, button, p, h1, h2, h3, h4, h5, h6, span, div, table {
            -webkit-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
        }
        input, textarea, select {
            -webkit-user-select: text;
            user-select: text;
        }
        .break-anywhere {
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .header-marquee {
            overflow: hidden;
            min-width: 0;
        }
        .header-marquee__track {
            display: inline-flex;
            width: max-content;
            white-space: nowrap;
            will-change: transform;
            animation: header-marquee-ltr 25s linear infinite;
        }
        .header-marquee__item {
            flex: 0 0 auto;
        }
        @keyframes header-marquee-ltr {
            from { transform: translateX(-50%); }
            to { transform: translateX(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .header-marquee__track {
                animation: none;
                transform: none;
            }
        }
        @media (max-width: 640px) {
            .glass {
                box-shadow: 0 18px 50px rgba(0,0,0,.32);
            }
            .mobile-wrap {
                white-space: normal;
                overflow-wrap: anywhere;
                word-break: break-word;
            }
        }
        .sidebar-shell {
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
        }
        .sidebar-menu-container {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding-right: 4px;
        }
        .sidebar-footer {
            flex-shrink: 0;
            padding-bottom: calc(1.25rem + env(safe-area-inset-bottom));
        }
        img, video {
            -webkit-user-drag: none;
        }
        @media print {
            body * { visibility: hidden !important; }
            body::before {
                content: "Nội dung được bảo vệ.";
                visibility: visible !important;
                display: grid;
                min-height: 100vh;
                place-items: center;
                color: #111;
                background: #fff;
                font: 700 24px Arial, sans-serif;
            }
        }
    </style>
</head>
{{-- @php($authSessionMeta = auth()->check() ? request()->attributes->get('auth_session_client_payload') : null) --}}
<body class="min-h-screen overflow-x-hidden bg-night text-white antialiased" x-data="{ sidebarOpen: false, notificationsOpen: false }" x-init="$nextTick(() => lucide.createIcons())">
<div class="pointer-events-none fixed inset-0 z-0 bg-[linear-gradient(rgba(255,255,255,.035)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.035)_1px,transparent_1px)] bg-[size:72px_72px]"></div>

<div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>

<aside class="sidebar-shell fixed inset-y-0 left-0 z-50 w-[85vw] max-w-[320px] -translate-x-full overflow-hidden border-r border-white/10 bg-[#070815]/90 px-4 py-5 shadow-2xl shadow-black/40 backdrop-blur-2xl transition duration-300 lg:w-72 lg:max-w-none lg:translate-x-0 lg:px-5 lg:py-6" :class="{ 'translate-x-0': sidebarOpen }">
    <a href="{{ auth()->check() ? (auth()->user()->isAccountant() ? route('accountant.dashboard') : route('dashboard')) : route('landing') }}" class="flex flex-shrink-0 items-center gap-3 rounded-2xl px-2 py-1 transition hover:bg-white/10">
        @if(!empty($brandSettings['logo_url']))
            <img src="{{ $brandSettings['logo_url'] }}" alt="{{ $brandSettings['name'] }}" class="h-12 w-12 rounded-2xl object-cover shadow-glow">
        @else
            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-amber-300 via-fuchsia-400 to-violet-600 shadow-glow">
                <i data-lucide="sparkles" class="h-6 w-6 text-white"></i>
            </div>
        @endif
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.28em] text-amber-200/80">{{ $brandSettings['eyebrow'] }}</p>
            <h1 class="text-lg font-bold">{{ $brandSettings['name'] }}</h1>
        </div>
    </a>

    <div class="sidebar-menu-container mt-6 lg:mt-9">
    <nav class="space-y-2">
        @auth
            <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ auth()->user()->isAccountant() ? route('accountant.dashboard') : route('dashboard') }}">
                <i data-lucide="layout-dashboard" class="h-5 w-5 text-violet-300"></i><span>Dashboard</span>
            </a>
            @if(auth()->user()->isAccountant() && ! auth()->user()->isAdmin())
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('accountant.transactions.index') }}">
                    <i data-lucide="receipt-text" class="h-5 w-5 text-sky-300"></i><span>Giao dịch</span>
                </a>
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('accountant.withdrawals.index') }}">
                    <i data-lucide="landmark" class="h-5 w-5 text-rose-300"></i><span>Rút tiền</span>
                </a>
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('accountant.deposits.index') }}">
                    <i data-lucide="arrow-down-up" class="h-5 w-5 text-emerald-300"></i><span>Nạp tiền</span>
                </a>
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('accountant.wallets.index') }}">
                    <i data-lucide="wallet-cards" class="h-5 w-5 text-amber-300"></i><span>Ví khách hàng</span>
                </a>
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('accountant.revenue') }}">
                    <i data-lucide="chart-no-axes-column-increasing" class="h-5 w-5 text-fuchsia-300"></i><span>Doanh thu</span>
                </a>
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('accountant.reports') }}">
                    <i data-lucide="file-spreadsheet" class="h-5 w-5 text-cyan-300"></i><span>Báo cáo</span>
                </a>
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('accountant.audit-logs') }}">
                    <i data-lucide="clipboard-list" class="h-5 w-5 text-violet-300"></i><span>Audit Log</span>
                </a>
            @else
                @unless(auth()->user()->is_admin)
                    <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('notifications.index') }}">
                        <i data-lucide="bell-ring" class="h-5 w-5 text-amber-300"></i><span>Thông báo</span>
                    </a>
                @endunless
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('billing') }}">
                    <i data-lucide="crown" class="h-5 w-5 text-amber-300"></i><span>Nâng cấp</span>
                </a>
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('affiliate.index') }}">
                    <i data-lucide="users-round" class="h-5 w-5 text-fuchsia-300"></i><span>Thành viên</span>
                </a>
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('wallet') }}">
                    <i data-lucide="wallet" class="h-5 w-5 text-emerald-300"></i><span>Ví số dư</span>
                </a>
                @unless(auth()->user()->is_admin)
                    <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('transactions.index') }}">
                        <i data-lucide="receipt-text" class="h-5 w-5 text-sky-300"></i><span>{!! html_entity_decode('L&#7883;ch s&#7917; giao d&#7883;ch') !!}</span>
                    </a>
                @endunless
            @endif
            <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('profile.edit') }}">
                <i data-lucide="user-round-cog" class="h-5 w-5 text-violet-300"></i><span>Hồ sơ</span>
            </a>
            @if(auth()->user()->is_admin)
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('admin.index') }}">
                    <i data-lucide="shield-check" class="h-5 w-5 text-sky-300"></i><span>Admin Console</span>
                </a>
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('admin.passwords') }}">
                    <i data-lucide="key-round" class="h-5 w-5 text-emerald-300"></i><span>Đổi pass user</span>
                </a>
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('admin.users.index') }}">
                    <i data-lucide="users" class="h-5 w-5 text-violet-300"></i><span>Quản trị user</span>
                </a>
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('admin.plans.index') }}">
                    <i data-lucide="badge-dollar-sign" class="h-5 w-5 text-emerald-300"></i><span>Quản lý gói</span>
                </a>
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('admin.reports.index') }}">
                    <i data-lucide="chart-column-big" class="h-5 w-5 text-fuchsia-300"></i><span>{!! html_entity_decode('Report b&#225;o c&#225;o') !!}</span>
                </a>
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('admin.notifications.index') }}">
                    <i data-lucide="megaphone" class="h-5 w-5 text-amber-300"></i><span>Thông báo</span>
                </a>
                @if(auth()->user()->is_admin)
                    <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('admin.email-otp.index') }}">
                        <i data-lucide="mail-search" class="h-5 w-5 text-sky-300"></i><span>Cấu hình Email OTP</span>
                    </a>
                @endif
                <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('admin.lessons.index') }}">
                    <i data-lucide="folder-pen" class="h-5 w-5 text-amber-300"></i><span>Nội dung học</span>
                </a>
            @endif
        @else
            <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('landing') }}">
                <i data-lucide="orbit" class="h-5 w-5 text-violet-300"></i><span>Trang chủ</span>
            </a>
            <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('login') }}">
                <i data-lucide="log-in" class="h-5 w-5 text-violet-300"></i><span>Đăng nhập</span>
            </a>
            <a class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm text-slate-300 transition hover:bg-white/10 hover:text-white hover:shadow-glow" href="{{ route('register') }}">
                <i data-lucide="user-plus" class="h-5 w-5 text-amber-300"></i><span>Đăng ký</span>
            </a>
        @endauth
    </nav>
    </div>

    @auth
        <div class="sidebar-footer mt-4">
            <div class="rounded-[24px] border border-violet-400/20 bg-violet-500/10 p-4 shadow-glow">
            <a href="{{ route('profile.edit') }}" class="mb-3 flex items-center gap-3 rounded-2xl p-2 transition hover:bg-white/10">
                <div class="h-10 w-10 rounded-2xl bg-cover bg-center" style="background-image:url('https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=160&q=80')"></div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold">{{ auth()->user()->isAccountant() ? html_entity_decode('K&#7871; to&#225;n') : auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
                </div>
            </a>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="flex w-full items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-sm text-slate-200 transition hover:bg-white/15">
                    <i data-lucide="log-out" class="h-4 w-4"></i> Đăng xuất
                </button>
            </form>
            </div>
        </div>
    @endauth
</aside>

@php($headerMarqueeText = \App\Models\SiteSetting::headerMarqueeText())
<div class="relative z-10 min-h-screen max-w-full overflow-x-hidden lg:pl-72">
    <header class="sticky top-0 z-30 border-b border-white/10 bg-night/65 backdrop-blur-2xl">
        <div class="px-4 py-3 sm:px-6 lg:px-10">
            <div class="flex min-h-14 w-full items-center justify-between gap-3 sm:min-h-[5rem]">
            <button class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-white/10 bg-white/5 lg:hidden" @click="sidebarOpen = true">
                <i data-lucide="menu" class="h-5 w-5"></i>
            </button>
            <div class="header-marquee hidden min-w-0 flex-1 px-6 sm:flex sm:items-center">
                <div class="w-full">
                    <div class="header-marquee__track text-sm font-medium text-slate-300 sm:text-[15px]">
                        <span class="header-marquee__item pr-16">Hệ thống sử dụng trải nghiệm hình ảnh và âm thanh mô phỏng trạng thái: Alpha, Theta, Deep Relaxation, Focus State.</span>
                        <span class="header-marquee__item pr-16" aria-hidden="true">Hệ thống sử dụng trải nghiệm hình ảnh và âm thanh mô phỏng trạng thái: Alpha, Theta, Deep Relaxation, Focus State.</span>
                    </div>
                </div>
            </div>
            <div class="ml-auto flex shrink-0 items-center gap-3">
                @auth
                    <div class="hidden rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-right sm:block">
                        <p class="text-xs text-slate-400">Số dư</p>
                        <p class="font-semibold">{{ number_format(auth()->user()->wallet?->balance_vnd ?? 0, 0, ',', '.') }} đ</p>
                    </div>
                @endauth
                @auth
                    <div class="relative" @click.outside="notificationsOpen = false">
                        <button type="button" class="relative grid h-11 w-11 place-items-center rounded-2xl border border-white/10 bg-white/5 transition hover:bg-white/10" @click="notificationsOpen = !notificationsOpen">
                            <i data-lucide="bell" class="h-5 w-5 text-amber-200"></i>
                            @if(($headerUnreadNotificationCount ?? 0) > 0)
                                <span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-violet-500 px-1 text-[10px] font-black text-white shadow-glow">{{ $headerUnreadNotificationCount }}</span>
                            @endif
                        </button>

                        <div x-show="notificationsOpen" x-cloak x-transition.opacity class="absolute right-0 mt-3 w-[min(92vw,420px)] overflow-hidden rounded-[28px] border border-white/10 bg-[#090a14]/95 shadow-2xl shadow-black/50 backdrop-blur-2xl">
                            <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[.22em] text-violet-200/70">Notifications</p>
                                    <h3 class="mt-1 text-lg font-black">Thông báo gần đây</h3>
                                </div>
                                <i data-lucide="bell-ring" class="h-5 w-5 text-amber-200"></i>
                            </div>

                            <div class="max-h-[480px] overflow-y-auto p-2">
                                @forelse(($headerNotifications ?? collect()) as $item)
                                    <div class="flex gap-3 rounded-[22px] px-3 py-3 transition hover:bg-white/[.05]">
                                        <div class="mt-1 grid h-10 w-10 shrink-0 place-items-center rounded-2xl
                                            {{ $item['tone'] === 'emerald' ? 'bg-emerald-400/10 text-emerald-200' : '' }}
                                            {{ $item['tone'] === 'rose' ? 'bg-rose-400/10 text-rose-200' : '' }}
                                            {{ $item['tone'] === 'fuchsia' ? 'bg-fuchsia-400/10 text-fuchsia-200' : '' }}
                                            {{ $item['tone'] === 'violet' ? 'bg-violet-400/10 text-violet-200' : '' }}">
                                            <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-3">
                                                <p class="font-semibold text-white">{{ $item['title'] }}</p>
                                                <span class="shrink-0 text-xs text-slate-500">{{ $item['time']->diffForHumans() }}</span>
                                            </div>
                                            <p class="mt-1 text-sm leading-6 text-slate-400">{{ $item['body'] }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-5 py-10 text-center text-sm text-slate-400">Chưa có thông báo mới.</div>
                                @endforelse
                            </div>

                            <div class="flex items-center justify-between gap-3 border-t border-white/10 px-5 py-3">
                                <p class="text-xs text-slate-500">Hiển thị tối đa 5 thông báo gần nhất.</p>
                                <form method="post" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    <button class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-bold text-violet-100 transition hover:bg-violet-400/20">
                                        Đã đọc tất cả
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="grid h-11 w-11 place-items-center rounded-2xl border border-white/10 bg-white/5">
                        <i data-lucide="bell" class="h-5 w-5 text-amber-200"></i>
                    </div>
                @endauth
            </div>

            </div>
            <div class="hidden">
                <div class="w-full rounded-2xl border border-white/10 bg-white/[.04] px-3 py-2">
                    <div class="header-marquee__track text-xs font-medium text-slate-300">
                        <span class="header-marquee__item pr-10">Há»‡ thá»‘ng sá»­ dá»¥ng tráº£i nghiá»‡m hÃ¬nh áº£nh vÃ  Ã¢m thanh mÃ´ phá»ng tráº¡ng thÃ¡i: Alpha, Theta, Deep Relaxation, Focus State.</span>
                        <span class="header-marquee__item pr-10" aria-hidden="true">Há»‡ thá»‘ng sá»­ dá»¥ng tráº£i nghiá»‡m hÃ¬nh áº£nh vÃ  Ã¢m thanh mÃ´ phá»ng tráº¡ng thÃ¡i: Alpha, Theta, Deep Relaxation, Focus State.</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-full overflow-x-hidden px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
        @if(session('status'))
            <div class="mb-6 flex items-center gap-3 rounded-[24px] border border-emerald-300/20 bg-emerald-400/10 px-5 py-4 text-emerald-100 shadow-lg shadow-emerald-950/30">
                <i data-lucide="check-circle-2" class="h-5 w-5"></i>{{ session('status') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-6 flex items-center gap-3 rounded-[24px] border border-rose-300/20 bg-rose-500/10 px-5 py-4 text-rose-100 shadow-lg shadow-rose-950/30">
                <i data-lucide="alert-triangle" class="h-5 w-5"></i>{{ $errors->first() }}
            </div>
        @endif
        @include('partials.voice-sample-popup')
        @yield('content')
    </main>
</div>

{{--
@auth
    @if($authSessionMeta)
        <div id="session-warning-modal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/70 px-4 backdrop-blur-sm">
            <div class="glass w-full max-w-xl rounded-[32px] p-6 shadow-glow sm:p-8">
                <div class="flex items-start gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-amber-400/20 text-amber-100">
                        <i data-lucide="timer-reset" class="h-6 w-6"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/80">Cảnh báo phiên đăng nhập</p>
                        <h2 class="mt-2 text-2xl font-black leading-tight">Phiên đăng nhập sắp hết hạn</h2>
                        <p class="mt-3 leading-7 text-slate-200">
                            Phiên đăng nhập của bạn sắp hết hạn. Vui lòng tiếp tục phiên làm việc nếu muốn duy trì đăng nhập.
                        </p>
                        <p class="mt-3 text-sm text-slate-300">
                            Còn lại: <span class="font-black text-white" data-session-warning-countdown>00:00</span>
                        </p>
                    </div>
                </div>
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <button type="button" id="session-continue-button" class="rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1">
                        Tiếp tục phiên làm việc
                    </button>
                    <button type="button" id="session-logout-button" class="rounded-2xl border border-white/10 bg-white/10 px-5 py-4 font-black text-slate-100 transition hover:bg-white/15">
                        Đăng xuất ngay
                    </button>
                </div>
            </div>
        </div>

        <form id="session-expire-form" method="post" action="{{ route('session.expire') }}" class="hidden">
            @csrf
        </form>
    @endif
@endauth
--}}

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('countdown', (target) => ({
            target: target ? new Date(target).getTime() : null,
            remaining: '00:00:00',
            init() {
                this.tick();
                setInterval(() => this.tick(), 1000);
            },
            tick() {
                if (!this.target) return;
                const distance = Math.max(0, this.target - Date.now());
                const h = String(Math.floor(distance / 3600000)).padStart(2, '0');
                const m = String(Math.floor((distance % 3600000) / 60000)).padStart(2, '0');
                const s = String(Math.floor((distance % 60000) / 1000)).padStart(2, '0');
                this.remaining = `${h}:${m}:${s}`;
            }
        }));
        Alpine.data('subscriptionCountdown', (target) => ({
            target: target ? new Date(target).getTime() : null,
            days: '0',
            time: '00:00:00',
            init() {
                this.tick();
                setInterval(() => this.tick(), 1000);
            },
            tick() {
                if (!this.target) return;
                const distance = Math.max(0, this.target - Date.now());
                this.days = String(Math.floor(distance / 86400000));
                const h = String(Math.floor((distance % 86400000) / 3600000)).padStart(2, '0');
                const m = String(Math.floor((distance % 3600000) / 60000)).padStart(2, '0');
                const s = String(Math.floor((distance % 60000) / 1000)).padStart(2, '0');
                this.time = `${h}:${m}:${s}`;
            }
        }));
        Alpine.data('lessonCountdown', (target) => ({
            target: target ? new Date(target).getTime() : null,
            days: '0',
            time: '00:00:00',
            expired: false,
            init() {
                this.tick();
                setInterval(() => this.tick(), 1000);
            },
            tick() {
                if (!this.target) return;
                const distance = Math.max(0, this.target - Date.now());
                this.days = String(Math.floor(distance / 86400000));
                const h = String(Math.floor((distance % 86400000) / 3600000)).padStart(2, '0');
                const m = String(Math.floor((distance % 3600000) / 60000)).padStart(2, '0');
                const s = String(Math.floor((distance % 60000) / 1000)).padStart(2, '0');
                this.time = `${h}:${m}:${s}`;

                if (distance === 0 && !this.expired) {
                    this.expired = true;
                    setTimeout(() => window.location.reload(), 700);
                }
            }
        }));
        Alpine.data('voiceSamplePrompt', (config) => ({
            open: true,
            hasUploaded: !!config.hasUploaded,
            deleteAfterAt: config.deleteAfterAt ? new Date(config.deleteAfterAt).getTime() : null,
            uploadUrl: config.uploadUrl,
            completeUrl: config.completeUrl,
            csrfToken: config.csrfToken,
            mediaRecorder: null,
            stream: null,
            chunks: [],
            audioUrl: null,
            isRecording: false,
            isBusy: false,
            message: '',
            error: false,
            timer: '00:00',
            deleteCountdown: '',
            timerStartedAt: null,
            init() {
                if (this.deleteAfterAt) {
                    this.tickDeleteCountdown();
                    setInterval(() => this.tickDeleteCountdown(), 1000);
                }
            },
            skip() {
                this.stopStream();
                this.open = false;
            },
            async startRecording() {
                this.message = '';
                this.error = false;

                if (!navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
                    this.error = true;
                    this.message = 'Trình duyệt này chưa hỗ trợ ghi âm trực tiếp.';
                    return;
                }

                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    const preferredMimeType = this.resolveRecorderMimeType();
                    this.mediaRecorder = preferredMimeType
                        ? new MediaRecorder(this.stream, { mimeType: preferredMimeType })
                        : new MediaRecorder(this.stream);
                    this.chunks = [];
                    this.timerStartedAt = Date.now();
                    this.updateTimer();
                    this.timerInterval = setInterval(() => this.updateTimer(), 1000);

                    this.mediaRecorder.ondataavailable = (event) => {
                        if (event.data.size > 0) {
                            this.chunks.push(event.data);
                        }
                    };

                    this.mediaRecorder.onstop = async () => {
                        clearInterval(this.timerInterval);
                        this.timer = '00:00';

                        const blobType = this.mediaRecorder.mimeType || this.chunks[0]?.type || 'audio/webm';
                        const blob = new Blob(this.chunks, { type: blobType });

                        if (!blob.size) {
                            this.error = true;
                            this.message = 'Không có dữ liệu ghi âm. Vui lòng thử lại.';
                            this.stopStream();
                            return;
                        }

                        if (this.audioUrl) {
                            URL.revokeObjectURL(this.audioUrl);
                        }

                        this.audioUrl = URL.createObjectURL(blob);
                        await this.uploadBlob(blob);
                        this.stopStream();
                    };

                    this.mediaRecorder.start();
                    this.isRecording = true;
                } catch (error) {
                    this.error = true;
                    this.message = 'Không thể truy cập micro. Vui lòng cho phép quyền ghi âm rồi thử lại.';
                }
            },
            stopRecording() {
                if (this.mediaRecorder && this.isRecording) {
                    this.isRecording = false;
                    this.mediaRecorder.stop();
                }
            },
            async uploadBlob(blob) {
                this.isBusy = true;
                this.message = 'Đang tải bản ghi lên hệ thống...';
                this.error = false;

                const extension = this.resolveRecordingExtension(blob.type);
                const formData = new FormData();
                formData.append('recording', new File([blob], `voice-sample.${extension}`, { type: blob.type || 'audio/webm' }));

                try {
                    const response = await fetch(this.uploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || 'Tải file ghi âm thất bại.');
                    }

                    this.hasUploaded = true;
                    this.deleteAfterAt = payload.delete_after_at ? new Date(payload.delete_after_at).getTime() : null;
                    this.tickDeleteCountdown();
                    this.message = payload.message || 'Đã tải bản ghi tạm thời lên hệ thống.';
                } catch (error) {
                    this.error = true;
                    this.message = error.message || 'Không thể tải bản ghi lên hệ thống.';
                } finally {
                    this.isBusy = false;
                }
            },
            async complete() {
                this.isBusy = true;
                this.message = 'Đang hoàn tất...';
                this.error = false;

                try {
                    const response = await fetch(this.completeUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || 'Không thể hoàn thành bước ghi âm.');
                    }

                    this.message = payload.message || 'Đã hoàn tất.';
                    setTimeout(() => window.location.reload(), 700);
                } catch (error) {
                    this.error = true;
                    this.message = error.message || 'Không thể hoàn thành bước ghi âm.';
                    this.isBusy = false;
                }
            },
            updateTimer() {
                if (!this.timerStartedAt) return;
                const totalSeconds = Math.floor((Date.now() - this.timerStartedAt) / 1000);
                const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
                const seconds = String(totalSeconds % 60).padStart(2, '0');
                this.timer = `${minutes}:${seconds}`;
            },
            resolveRecorderMimeType() {
                const candidates = [
                    'audio/webm;codecs=opus',
                    'audio/webm',
                    'audio/mp4',
                    'video/mp4',
                    'audio/ogg;codecs=opus',
                    'audio/ogg',
                ];

                if (typeof MediaRecorder.isTypeSupported !== 'function') {
                    return null;
                }

                return candidates.find((candidate) => MediaRecorder.isTypeSupported(candidate)) || null;
            },
            resolveRecordingExtension(mimeType) {
                const normalizedMimeType = String(mimeType || '').toLowerCase();

                if (normalizedMimeType.includes('mp4') || normalizedMimeType.includes('m4a')) {
                    return 'm4a';
                }

                if (normalizedMimeType.includes('mpeg') || normalizedMimeType.includes('mp3')) {
                    return 'mp3';
                }

                if (normalizedMimeType.includes('wav')) {
                    return 'wav';
                }

                if (normalizedMimeType.includes('ogg')) {
                    return 'ogg';
                }

                if (normalizedMimeType.includes('3gpp') || normalizedMimeType.includes('3gp')) {
                    return '3gp';
                }

                return 'webm';
            },
            tickDeleteCountdown() {
                if (!this.deleteAfterAt) {
                    this.deleteCountdown = '';
                    return;
                }

                const diff = Math.max(0, this.deleteAfterAt - Date.now());

                if (diff === 0) {
                    this.deleteCountdown = '00:00';
                    return;
                }

                const totalSeconds = Math.ceil(diff / 1000);
                const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
                const seconds = String(totalSeconds % 60).padStart(2, '0');
                this.deleteCountdown = `${minutes}:${seconds}`;
            },
            stopStream() {
                this.stream?.getTracks().forEach((track) => track.stop());
                this.stream = null;
            },
        }));
    });

    {{--
    (() => {
        const sessionMeta = @json($authSessionMeta);
        const modal = document.getElementById('session-warning-modal');
        const continueButton = document.getElementById('session-continue-button');
        const logoutButton = document.getElementById('session-logout-button');
        const expireForm = document.getElementById('session-expire-form');
        const countdown = document.querySelector('[data-session-warning-countdown]');

        if (!sessionMeta || !modal || !continueButton || !logoutButton || !expireForm || !countdown) {
            return;
        }

        const csrfToken = @json(csrf_token());
        let warningVisible = false;
        let idleExpiresAt = new Date(sessionMeta.idle_expires_at).getTime();
        let absoluteExpiresAt = new Date(sessionMeta.absolute_expires_at).getTime();
        const warningSeconds = Number(sessionMeta.warning_seconds || 300);
        let heartbeatPending = false;

        const effectiveExpiry = () => Math.min(idleExpiresAt, absoluteExpiresAt);

        const formatSeconds = (seconds) => {
            const safe = Math.max(0, Math.ceil(seconds));
            const minutes = String(Math.floor(safe / 60)).padStart(2, '0');
            const secs = String(safe % 60).padStart(2, '0');

            return `${minutes}:${secs}`;
        };

        const showWarning = () => {
            if (warningVisible) {
                return;
            }

            warningVisible = true;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        const hideWarning = () => {
            warningVisible = false;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        const expireNow = () => {
            expireForm.submit();
        };

        const tick = () => {
            const remainingSeconds = (effectiveExpiry() - Date.now()) / 1000;

            if (remainingSeconds <= 0) {
                expireNow();
                return;
            }

            countdown.textContent = formatSeconds(remainingSeconds);

            if (remainingSeconds <= warningSeconds) {
                showWarning();
            } else if (warningVisible && !heartbeatPending) {
                hideWarning();
            }
        };

        continueButton.addEventListener('click', async () => {
            if (heartbeatPending) {
                return;
            }

            heartbeatPending = true;
            continueButton.disabled = true;

            try {
                const response = await fetch(@json(route('session.heartbeat')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ keep_alive: true }),
                });

                if (!response.ok) {
                    expireNow();
                    return;
                }

                const payload = await response.json();

                idleExpiresAt = new Date(payload.session.idle_expires_at).getTime();
                absoluteExpiresAt = new Date(payload.session.absolute_expires_at).getTime();
                hideWarning();
                tick();
            } catch (error) {
                expireNow();
            } finally {
                heartbeatPending = false;
                continueButton.disabled = false;
            }
        });

        logoutButton.addEventListener('click', () => {
            expireNow();
        });

        tick();
        setInterval(tick, 1000);
    })();
    --}}
    window.addEventListener('load', () => lucide.createIcons());

    (() => {
        const marqueeText = @js($headerMarqueeText);

        document.querySelectorAll('.header-marquee__item').forEach((item) => {
            item.textContent = marqueeText;
        });
    })();

    (() => {
        const navLabelMap = new Map([
            ['/affiliate', 'Thành viên'],
            ['/wallet', 'Ví số dư'],
            ['/profile', 'Hồ sơ'],
            ['/login', 'Đăng nhập'],
            ['/register', 'Đăng ký'],
            ['/admin/email-otp', 'Cấu hình Email OTP'],
            ['/admin/notifications', 'Thông báo'],
            ['/admin/plans', 'Quản lý gói'],
            ['/admin/lessons', 'Nội dung học'],
            ['/admin/passwords', 'Đổi pass user'],
        ]);

        navLabelMap.forEach((label, path) => {
            const anchor = document.querySelector(`aside nav a[href="${path}"] span`);

            if (anchor) {
                anchor.textContent = label;
            }
        });

        const balanceLabel = document.querySelector('header .text-right p.text-xs.text-slate-400');
        if (balanceLabel) {
            balanceLabel.textContent = 'Số dư';
        }

        const notificationPanel = document.querySelector('[x-show="notificationsOpen"]');
        if (notificationPanel) {
            const heading = notificationPanel.querySelector('h3');
            const empty = notificationPanel.querySelector('.max-h-\\[480px\\] .text-center');
            const footer = notificationPanel.querySelector('.border-t p.text-xs');
            const markAll = notificationPanel.querySelector('.border-t button');

            if (heading) {
                heading.textContent = 'Thông báo gần đây';
            }

            if (empty) {
                empty.textContent = 'Chưa có thông báo mới.';
            }

            if (footer) {
                footer.textContent = 'Hiển thị tối đa 5 thông báo gần nhất.';
            }

            if (markAll) {
                markAll.textContent = 'Đã đọc tất cả';
            }
        }
    })();

    (() => {
        const isEditable = (target) => {
            const element = target instanceof Element ? target : null;
            return element?.closest('input, textarea, select, [contenteditable="true"]');
        };

        document.addEventListener('contextmenu', (event) => event.preventDefault(), true);
        document.addEventListener('dragstart', (event) => event.preventDefault(), true);
        document.addEventListener('copy', (event) => {
            event.preventDefault();
            event.clipboardData?.setData('text/plain', '');
        }, true);
        document.addEventListener('cut', (event) => event.preventDefault(), true);
        document.addEventListener('selectstart', (event) => {
            if (!isEditable(event.target)) {
                event.preventDefault();
            }
        }, true);
        document.addEventListener('keydown', async (event) => {
            const key = event.key.toLowerCase();
            const blocked =
                event.key === 'F12' ||
                (event.ctrlKey && event.shiftKey && ['i', 'j', 'c'].includes(key)) ||
                (event.ctrlKey && ['u', 's', 'p'].includes(key)) ||
                key === 'printscreen';

            if (blocked) {
                event.preventDefault();
                event.stopPropagation();
                try {
                    await navigator.clipboard?.writeText('');
                } catch (error) {
                    // Clipboard access is browser-controlled; ignore when unavailable.
                }
            }
        }, true);
    })();
</script>
</body>
</html>
