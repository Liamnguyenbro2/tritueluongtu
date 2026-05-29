@php
    $brand = \App\Models\SiteSetting::branding();
    $primaryCtaUrl = auth()->check() ? route('dashboard') : route('register');
    $primaryCtaLabel = auth()->check() ? 'Mở bảng điều khiển' : 'Bắt đầu trải nghiệm';
@endphp
<!doctype html>
<html lang="vi" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $brand['name'] }} | Kích hoạt sóng lượng tử</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Manrope', 'sans-serif'],
                    },
                    colors: {
                        nebula: '#060816',
                        violetCore: '#7b2cff',
                        violetGlow: '#d26dff',
                        cyanGlow: '#53d3ff',
                        cardEdge: 'rgba(255,255,255,.12)',
                    },
                    boxShadow: {
                        halo: '0 0 80px rgba(158, 88, 255, .28)',
                        card: '0 28px 90px rgba(5, 8, 27, .55)',
                    },
                },
            },
        };
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        :root {
            color-scheme: dark;
        }
        html {
            scroll-behavior: smooth;
        }
        body {
            font-family: 'Manrope', sans-serif;
            background:
                radial-gradient(circle at 16% 14%, rgba(123, 44, 255, .26), transparent 26%),
                radial-gradient(circle at 74% 12%, rgba(210, 109, 255, .18), transparent 22%),
                radial-gradient(circle at 83% 39%, rgba(83, 211, 255, .10), transparent 20%),
                linear-gradient(180deg, #040714 0%, #060818 42%, #050612 100%);
            color: #f7f2ff;
            overflow-x: hidden;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                radial-gradient(rgba(255,255,255,.22) 0.7px, transparent 0.7px),
                linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px);
            background-size: 90px 90px, 90px 90px, 90px 90px;
            background-position: 0 0, 0 0, 0 0;
            mask-image: linear-gradient(180deg, rgba(0,0,0,.95), rgba(0,0,0,.45));
            opacity: .45;
        }
        .glass-panel {
            background: linear-gradient(180deg, rgba(15, 19, 45, .82), rgba(8, 12, 29, .72));
            border: 1px solid rgba(255,255,255,.09);
            box-shadow: 0 20px 60px rgba(0, 0, 0, .38), inset 0 1px 0 rgba(255,255,255,.06);
            backdrop-filter: blur(24px);
        }
        .section-shell {
            border-radius: 32px;
            border: 1px solid rgba(255,255,255,.08);
            background: linear-gradient(180deg, rgba(11, 14, 34, .92), rgba(10, 12, 28, .84));
            box-shadow: 0 28px 90px rgba(5, 8, 27, .55);
        }
        .nebula-button {
            background: linear-gradient(90deg, #b23dff 0%, #8e5bff 48%, #526dff 100%);
            box-shadow: 0 0 0 1px rgba(255,255,255,.10), 0 14px 40px rgba(151, 86, 255, .38);
        }
        .hero-rings,
        .brain-rings {
            position: absolute;
            inset: 50%;
            transform: translate(-50%, -50%);
            border-radius: 999px;
            border: 1px solid rgba(214, 132, 255, .22);
            box-shadow: 0 0 40px rgba(162, 83, 255, .16), inset 0 0 30px rgba(255,255,255,.02);
        }
        .hero-figure {
            position: absolute;
            inset: 50% auto auto 50%;
            transform: translate(-50%, -48%);
            width: min(22vw, 180px);
            height: min(22vw, 180px);
            border-radius: 999px 999px 42% 42%;
            background:
                radial-gradient(circle at 50% 36%, rgba(255,255,255,.72), rgba(255,255,255,.12) 18%, transparent 19%),
                radial-gradient(circle at 50% 52%, rgba(236, 169, 255, .78), rgba(139, 84, 255, .24) 28%, transparent 56%),
                linear-gradient(180deg, rgba(12,9,26,.12) 0%, rgba(8,6,18,.88) 100%);
            box-shadow: 0 0 60px rgba(223, 137, 255, .48);
            filter: saturate(1.12);
        }
        .wave-line {
            position: absolute;
            left: 50%;
            width: min(100%, 620px);
            height: 140px;
            transform: translateX(-50%);
            opacity: .8;
        }
        .wave-line svg {
            width: 100%;
            height: 100%;
            overflow: visible;
        }
        .wave-line path {
            fill: none;
            stroke-linecap: round;
        }
        .cta-landscape {
            background:
                radial-gradient(circle at 50% 20%, rgba(181, 98, 255, .30), transparent 34%),
                linear-gradient(180deg, rgba(22, 20, 59, .18), rgba(8, 11, 28, .72)),
                linear-gradient(180deg, #141433 0%, #0b0f25 100%);
        }
        .mountain-layer {
            position: absolute;
            inset: auto 0 0 0;
            height: 38%;
            background: linear-gradient(180deg, transparent, rgba(4, 8, 20, .2) 18%, rgba(4, 8, 20, .92) 100%);
            clip-path: polygon(0 100%, 0 54%, 8% 62%, 18% 38%, 30% 67%, 42% 45%, 56% 69%, 68% 41%, 79% 58%, 89% 33%, 100% 60%, 100% 100%);
        }
        .mountain-layer.layer-2 {
            height: 28%;
            opacity: .76;
            clip-path: polygon(0 100%, 0 68%, 13% 51%, 27% 74%, 41% 48%, 56% 71%, 72% 44%, 87% 64%, 100% 51%, 100% 100%);
        }
        .text-glow {
            text-shadow: 0 0 20px rgba(210, 109, 255, .18);
        }
        .hero-title-gradient {
            background: linear-gradient(180deg, #ffffff 0%, #f2deff 28%, #cc9cff 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .muted-border {
            border: 1px solid rgba(255,255,255,.12);
        }
    </style>
</head>
<body x-data="{ mobileMenu: false }" x-init="$nextTick(() => lucide.createIcons())">
    <div class="relative z-10">
        <header class="sticky top-0 z-50 border-b border-white/6 bg-[#050816]/72 backdrop-blur-2xl">
            <div class="mx-auto flex max-w-[1480px] items-center gap-4 px-5 py-4 lg:px-8">
                <a href="{{ route('landing') }}" class="flex min-w-0 items-center gap-3">
                    @if (!empty($brand['logo_url']))
                        <img
                            src="{{ $brand['logo_url'] }}"
                            alt="{{ $brand['name'] }}"
                            class="h-14 w-14 shrink-0 rounded-full border border-white/12 object-cover shadow-halo"
                        >
                    @else
                        <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-full border border-white/12 bg-[radial-gradient(circle_at_30%_30%,rgba(219,119,255,.9),rgba(71,28,151,.95)_58%,rgba(5,8,25,.98)_100%)] shadow-halo">
                            <div class="h-10 w-10 rounded-full border border-white/15 bg-[conic-gradient(from_180deg,#56d2ff,#7d3cff,#dd6bff,#56d2ff)] opacity-90 blur-[1px]"></div>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[.32em] text-[#ecd48e]">{{ $brand['eyebrow'] }}</p>
                        <p class="text-[1.55rem] font-extrabold leading-none text-white">{{ $brand['name'] }}</p>
                    </div>
                </a>

                <nav class="ml-10 hidden items-center gap-8 text-[1rem] font-medium text-white/78 lg:flex">
                    <a href="#top" class="transition hover:text-white">Trang chủ</a>
                    <a href="#experience" class="transition hover:text-white">Trải nghiệm</a>
                    <a href="#technology" class="transition hover:text-white">Công nghệ</a>
                    <a href="#benefits" class="transition hover:text-white">Lợi ích</a>
                    <a href="#stories" class="transition hover:text-white">Cảm nhận</a>
                    <a href="#pricing" class="transition hover:text-white">Giá</a>
                    <a href="#guide" class="transition hover:text-white">Hướng dẫn</a>
                </nav>

                <div class="ml-auto hidden items-center gap-4 lg:flex">
                    <a href="{{ route('login') }}" class="nebula-button rounded-2xl px-7 py-4 text-sm font-extrabold text-white transition hover:translate-y-[-1px]">
                        Đăng nhập
                    </a>
                    <a href="{{ route('login') }}" class="grid h-12 w-12 place-items-center rounded-2xl border border-white/10 bg-white/[.03] text-white/80 transition hover:bg-white/[.08] hover:text-white">
                        <i data-lucide="bell" class="h-5 w-5"></i>
                    </a>
                </div>

                <button type="button" class="ml-auto grid h-12 w-12 place-items-center rounded-2xl border border-white/10 bg-white/[.03] text-white/85 lg:hidden" @click="mobileMenu = !mobileMenu">
                    <i data-lucide="menu" class="h-5 w-5"></i>
                </button>
            </div>

            <div x-show="mobileMenu" x-cloak x-transition class="border-t border-white/8 bg-[#060916]/95 px-5 py-5 lg:hidden">
                <div class="flex flex-col gap-4 text-base font-medium text-white/80">
                    <a href="#top" @click="mobileMenu = false">Trang chủ</a>
                    <a href="#experience" @click="mobileMenu = false">Trải nghiệm</a>
                    <a href="#technology" @click="mobileMenu = false">Công nghệ</a>
                    <a href="#benefits" @click="mobileMenu = false">Lợi ích</a>
                    <a href="#stories" @click="mobileMenu = false">Cảm nhận</a>
                    <a href="#pricing" @click="mobileMenu = false">Giá</a>
                    <a href="#guide" @click="mobileMenu = false">Hướng dẫn</a>
                    <div class="flex flex-col gap-3 pt-3">
                        <a href="{{ $primaryCtaUrl }}" class="nebula-button rounded-2xl px-6 py-4 text-center text-sm font-extrabold text-white">{{ $primaryCtaLabel }}</a>
                        <a href="{{ route('login') }}" class="rounded-2xl border border-white/10 bg-white/[.03] px-6 py-4 text-center text-sm font-bold text-white/90">Đăng nhập</a>
                    </div>
                </div>
            </div>
        </header>

        <main id="top" class="mx-auto max-w-[1480px] px-4 pb-12 pt-6 sm:px-6 lg:px-8 lg:pb-20">
            <section class="section-shell relative overflow-hidden px-6 py-8 sm:px-8 lg:px-10 lg:py-10">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_18%,rgba(185,100,255,.12),transparent_26%),radial-gradient(circle_at_76%_28%,rgba(154,83,255,.16),transparent_28%),radial-gradient(circle_at_82%_60%,rgba(83,211,255,.09),transparent_20%)]"></div>
                <div class="relative grid items-center gap-10 xl:grid-cols-[1.08fr_.92fr]">
                    <div class="pb-3 pr-0 lg:pb-6 xl:pr-6">
                        <div class="inline-flex items-center gap-2 rounded-full border border-violet-200/14 bg-violet-300/8 px-4 py-2 text-[.88rem] font-semibold uppercase tracking-[.18em] text-[#ead2ff]">
                            <i data-lucide="sparkles" class="h-4 w-4 text-[#dca1ff]"></i>
                            Không chỉ là thư giãn
                        </div>

                        <h1 class="text-glow mt-12 max-w-[11.5ch] text-[3.15rem] font-extrabold uppercase leading-[1.02] tracking-[-.05em] text-white sm:text-[3.55rem] lg:text-[5.75rem] xl:text-[4.25rem]">
                            <span class="block">Kích hoạt</span>
                            <span class="hero-title-gradient mt-2 block sm:mt-3">sóng lượng tử</span>
                        </h1>

                        <p class="mt-7 max-w-[16ch] text-[1.65rem] font-medium leading-[1.28] text-white/92 sm:text-[1.95rem] lg:text-[2.15rem]">
                            Đồng bộ năng lượng - Bộ não - Cảm xúc - Tần số cơ thể
                        </p>

                        <p class="mt-6 max-w-[680px] text-[1rem] leading-8 text-white/60 sm:text-[1.08rem] sm:leading-9">
                            Trải nghiệm đồng bộ năng lượng thế hệ mới thông qua hình ảnh ánh sáng năng lượng, tần số âm thanh thư giãn, đồng bộ sóng não và công nghệ hiệu ứng cơ thể.
                        </p>

                        <div class="mt-9 flex flex-col gap-4 sm:flex-row">
                            <a href="{{ $primaryCtaUrl }}" class="nebula-button inline-flex items-center justify-center gap-3 rounded-2xl px-7 py-5 text-base font-extrabold text-white transition hover:translate-y-[-1px]">
                                <i data-lucide="rocket" class="h-5 w-5"></i>
                                Bắt đầu trải nghiệm ngay
                            </a>
                            <a href="#technology" class="inline-flex items-center justify-center gap-3 rounded-2xl border border-white/14 bg-white/[.03] px-7 py-5 text-base font-bold text-white/92 transition hover:bg-white/[.06]">
                                <i data-lucide="play" class="h-5 w-5"></i>
                                Tìm hiểu công nghệ
                            </a>
                        </div>

                        <div class="muted-border mt-8 max-w-[690px] rounded-2xl bg-white/[.02] px-5 py-4 text-sm leading-7 text-white/46">
                            <div class="flex items-start gap-3">
                                <i data-lucide="info" class="mt-1 h-4 w-4 shrink-0 text-white/40"></i>
                                <p>Lưu ý: Nội dung mang tính chất trải nghiệm thư giãn, thiền định và phát triển tinh thần cá nhân. Không thay thế cho tư vấn hoặc điều trị y khoa.</p>
                            </div>
                        </div>
                    </div>

                    <div class="relative min-h-[420px] overflow-hidden rounded-[32px] lg:min-h-[760px]">
                        <div class="absolute inset-0 rounded-[32px] bg-[radial-gradient(circle_at_50%_46%,rgba(228,142,255,.35),rgba(127,68,255,.12)_34%,transparent_58%),linear-gradient(180deg,rgba(10,9,31,.12),rgba(10,7,22,.72))]"></div>
                        <div class="absolute inset-[7%] rounded-full bg-[radial-gradient(circle,rgba(255,201,252,.45),rgba(135,73,255,.18)_42%,transparent_70%)] blur-3xl"></div>
                        <div class="hero-rings h-[88%] w-[88%]"></div>
                        <div class="hero-rings h-[72%] w-[72%]"></div>
                        <div class="hero-rings h-[56%] w-[56%]"></div>
                        <div class="hero-rings h-[40%] w-[40%]"></div>
                        <div class="hero-rings h-[22%] w-[22%] border-[#f4b3ff]/45"></div>
                        <div class="wave-line top-[34%]">
                            <svg viewBox="0 0 1000 180" preserveAspectRatio="none">
                                <path d="M0,94 C80,40 130,150 220,94 S360,40 460,94 620,150 720,94 870,40 1000,94" stroke="rgba(172,113,255,.84)" stroke-width="3"></path>
                                <path d="M0,94 C80,120 130,30 220,94 S360,150 460,94 620,30 720,94 870,150 1000,94" stroke="rgba(111,215,255,.42)" stroke-width="2"></path>
                            </svg>
                        </div>
                        <div class="hero-figure"></div>
                        <div class="absolute inset-x-[18%] bottom-[14%] h-20 rounded-full bg-[radial-gradient(circle,rgba(220,137,255,.56),rgba(109,67,255,.24)_44%,transparent_76%)] blur-xl"></div>
                        <div class="absolute inset-x-[12%] bottom-[7%] h-28 rounded-[999px] border border-violet-200/18 bg-[radial-gradient(circle,rgba(120,72,255,.18),rgba(120,72,255,.05)_56%,transparent_72%)]"></div>
                    </div>
                </div>
            </section>

            <section id="experience" class="section-shell mt-7 px-6 py-7 sm:px-8 lg:px-10">
                <h2 class="text-center text-[1.7rem] font-extrabold uppercase tracking-[.05em] text-[#f0b8ff] sm:text-[2.15rem]">
                    Trải nghiệm giúp bạn
                </h2>
                <div class="mt-7 grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                    @foreach ([
                        ['icon' => 'zap', 'title' => 'Tăng năng lượng', 'text' => 'Cảm nhận nguồn năng lượng tích cực mới mỗi ngày'],
                        ['icon' => 'target', 'title' => 'Tập trung cao độ', 'text' => 'Nâng cao khả năng tập trung và hiệu suất làm việc'],
                        ['icon' => 'moon-star', 'title' => 'Ngủ sâu hơn', 'text' => 'Cải thiện chất lượng giấc ngủ tự nhiên và sâu giấc'],
                        ['icon' => 'heart', 'title' => 'Cân bằng cảm xúc', 'text' => 'Giảm căng thẳng, lo âu và cảm xúc tiêu cực'],
                        ['icon' => 'flower-2', 'title' => 'Bình an nội tâm', 'text' => 'Kết nối sâu hơn với bản thân và tìm thấy sự bình an'],
                        ['icon' => 'activity', 'title' => 'Đồng bộ toàn diện', 'text' => 'Đồng bộ năng lượng - bộ não - cảm xúc - tần số cơ thể'],
                    ] as $item)
                        <article class="glass-panel rounded-[24px] px-5 py-7 text-center">
                            <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-[radial-gradient(circle_at_30%_30%,rgba(223,136,255,.25),rgba(98,67,255,.08))] text-[#df95ff] shadow-halo">
                                <i data-lucide="{{ $item['icon'] }}" class="h-8 w-8"></i>
                            </div>
                            <h3 class="mt-5 text-[1.18rem] font-bold text-white">{{ $item['title'] }}</h3>
                            <p class="mt-3 text-[.95rem] leading-7 text-white/55">{{ $item['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section id="technology" class="section-shell mt-7 grid gap-7 px-6 py-7 sm:px-8 lg:grid-cols-[.95fr_1.1fr] lg:px-10 lg:py-9">
                <div class="flex flex-col justify-center">
                    <div class="inline-flex w-fit items-center gap-2 rounded-full border border-fuchsia-200/14 bg-fuchsia-300/8 px-4 py-2 text-[.85rem] font-semibold uppercase tracking-[.18em] text-[#f0b6ff]">
                        <i data-lucide="badge-check" class="h-4 w-4"></i>
                        Công nghệ độc quyền
                    </div>
                    <h2 class="mt-6 max-w-[12ch] text-[2.7rem] font-extrabold uppercase leading-[1.03] tracking-[-.05em] text-white sm:text-[4rem]">
                        Công nghệ
                        <span class="hero-title-gradient mt-2 block sm:mt-3">đồng bộ tần số não bộ</span>
                    </h2>
                    <p class="mt-5 max-w-[680px] text-[1rem] leading-8 text-white/62 sm:text-lg sm:leading-9">
                        Hệ thống sử dụng trải nghiệm hình ảnh và âm thanh mô phỏng trạng thái sóng não tối ưu, giúp não bộ yên tĩnh, giảm áp lực và nâng cao hiệu suất.
                    </p>
                    <ul class="mt-8 space-y-4 text-[1rem] text-white/72">
                        @foreach ([
                            'Sóng ánh sáng chuyển động & hiệu ứng cộng hưởng năng lượng',
                            'Không gian âm thanh thiên nhiên & tần số thư giãn đa tầng',
                            'Đồng bộ sóng não theo thời gian thực',
                            'Cá nhân hóa trải nghiệm theo trạng thái của bạn',
                        ] as $line)
                            <li class="flex items-start gap-3">
                                <span class="mt-[2px] grid h-6 w-6 shrink-0 place-items-center rounded-full bg-emerald-400/12 text-emerald-200">
                                    <i data-lucide="check" class="h-4 w-4"></i>
                                </span>
                                <span>{{ $line }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="grid gap-5 xl:grid-cols-[.85fr_1.15fr]">
                    <article class="glass-panel rounded-[28px] px-5 py-5">
                        <p class="text-sm font-semibold uppercase tracking-[.16em] text-white/75">Trạng thái sóng não</p>
                        <div class="mt-4 space-y-3">
                            @foreach ([
                                ['name' => 'Alpha', 'desc' => 'Thư giãn nhẹ, tập trung', 'color' => 'from-[#7b5dff] to-[#c658ff]'],
                                ['name' => 'Theta', 'desc' => 'Thiền sâu, sáng tạo', 'color' => 'from-[#3b8dff] to-[#7d5fff]'],
                                ['name' => 'Deep Relaxation', 'desc' => 'Thư giãn sâu, phục hồi', 'color' => 'from-[#965cff] to-[#ed79ff]'],
                                ['name' => 'Focus State', 'desc' => 'Tập trung cao, hiệu suất', 'color' => 'from-[#3abdf6] to-[#7468ff]'],
                            ] as $state)
                                <div class="rounded-[22px] border border-white/8 bg-white/[.03] px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-gradient-to-br {{ $state['color'] }} text-white shadow-halo">
                                            <i data-lucide="sparkles" class="h-4 w-4"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-bold text-white">{{ $state['name'] }}</p>
                                            <p class="mt-1 text-sm text-white/55">{{ $state['desc'] }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 h-10 rounded-full bg-[linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.01))] px-3 py-2">
                                        <svg viewBox="0 0 260 32" class="h-full w-full">
                                            <path d="M0,16 C10,16 10,6 20,6 S30,26 40,26 50,12 60,12 70,20 80,20 90,5 100,5 110,24 120,24 130,16 140,16 150,8 160,8 170,28 180,28 190,14 200,14 210,18 220,18 230,6 240,6 250,16 260,16" stroke="rgba(165,111,255,.88)" stroke-width="2" fill="none" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>

                    <article class="glass-panel relative overflow-hidden rounded-[28px] min-h-[440px]">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_52%_44%,rgba(223,128,255,.28),rgba(118,72,255,.08)_32%,transparent_55%)]"></div>
                        <div class="brain-rings h-[78%] w-[78%]"></div>
                        <div class="brain-rings h-[56%] w-[56%] border-[#94d4ff]/25"></div>
                        <div class="absolute inset-[18%] rounded-full bg-[radial-gradient(circle,rgba(195,138,255,.35),rgba(83,211,255,.10)_52%,transparent_64%)] blur-2xl"></div>
                        <div class="absolute inset-x-[21%] bottom-[11%] h-12 rounded-full bg-[radial-gradient(circle,rgba(208,134,255,.50),rgba(90,72,255,.18)_48%,transparent_72%)] blur-md"></div>
                        <div class="absolute inset-x-[18%] bottom-[14%] h-20 rounded-full border border-violet-200/14 bg-[radial-gradient(circle,rgba(112,81,255,.18),rgba(112,81,255,.04)_60%,transparent_74%)]"></div>
                        <div class="absolute inset-0 flex items-center justify-center px-8">
                            <div class="relative w-full max-w-[380px]">
                                <div class="absolute inset-[14%] rounded-[48%] bg-[radial-gradient(circle_at_50%_50%,rgba(246,182,255,.95),rgba(183,96,255,.36)_34%,rgba(75,96,255,.20)_64%,transparent_78%)] blur-[2px]"></div>
                                <svg viewBox="0 0 420 320" class="relative w-full drop-shadow-[0_0_26px_rgba(190,126,255,.34)]">
                                    <defs>
                                        <linearGradient id="brainStroke" x1="0%" x2="100%">
                                            <stop offset="0%" stop-color="#66d2ff" />
                                            <stop offset="52%" stop-color="#b56cff" />
                                            <stop offset="100%" stop-color="#ff89d9" />
                                        </linearGradient>
                                    </defs>
                                    <path d="M120 242 C62 228 52 158 92 122 C86 70 138 42 182 62 C220 24 286 30 312 80 C350 86 372 130 356 168 C366 214 330 252 286 252 C264 274 222 280 194 262 C174 272 146 268 120 242 Z" fill="rgba(23,18,62,.55)" stroke="url(#brainStroke)" stroke-width="5"/>
                                    <path d="M148 92 C174 116 172 140 154 166" stroke="url(#brainStroke)" stroke-width="3" fill="none"/>
                                    <path d="M200 78 C224 110 222 132 206 158" stroke="url(#brainStroke)" stroke-width="3" fill="none"/>
                                    <path d="M254 84 C274 108 276 142 258 176" stroke="url(#brainStroke)" stroke-width="3" fill="none"/>
                                    <path d="M130 178 C182 150 244 150 304 176" stroke="url(#brainStroke)" stroke-width="3" fill="none"/>
                                    <path d="M150 214 C190 192 242 194 286 218" stroke="url(#brainStroke)" stroke-width="3" fill="none"/>
                                </svg>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section id="stories" class="section-shell mt-7 px-6 py-7 sm:px-8 lg:px-10">
                <h2 class="text-center text-[1.75rem] font-extrabold uppercase tracking-[.05em] text-[#f0b8ff] sm:text-[2.2rem]">
                    Cảm nhận từ người dùng
                </h2>
                <div class="mt-7 grid gap-5 lg:grid-cols-[56px_1fr_56px]">
                    <button type="button" class="hidden items-center justify-center rounded-full border border-white/10 bg-white/[.03] text-white/70 transition hover:bg-white/[.07] hover:text-white lg:flex">
                        <i data-lucide="arrow-left" class="h-5 w-5"></i>
                    </button>
                    <div class="grid gap-5 xl:grid-cols-3">
                        @foreach ([
                            ['name' => 'Minh Anh', 'role' => 'Nhân viên văn phòng', 'quote' => 'Sau 2 tuần trải nghiệm, tôi ngủ ngon hơn, giảm hẳn căng thẳng và cảm thấy năng lượng tích cực hơn mỗi ngày.'],
                            ['name' => 'Hoàng Nam', 'role' => 'Doanh nhân', 'quote' => 'Công việc của tôi đòi hỏi tập trung cao độ. Trải nghiệm này giúp tôi duy trì sự tập trung và bình tĩnh trong mọi tình huống.'],
                            ['name' => 'Thu Hà', 'role' => 'Giáo viên', 'quote' => 'Tôi cảm thấy kết nối sâu hơn với bản thân, cảm xúc cân bằng và cuộc sống trở nên ý nghĩa hơn.'],
                        ] as $index => $story)
                            <article class="glass-panel rounded-[28px] px-6 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-full border border-white/15 bg-[radial-gradient(circle_at_30%_30%,rgba(255,215,165,.75),rgba(161,76,255,.55))] text-lg font-black text-white">
                                        {{ mb_substr($story['name'], 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-lg font-bold text-white">{{ $story['name'] }}</p>
                                        <p class="mt-1 text-sm text-white/55">{{ $story['role'] }}</p>
                                        <div class="mt-2 flex items-center gap-1 text-[#ffcf59]">
                                            @for ($star = 0; $star < 5; $star++)
                                                <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-6 text-[1rem] leading-8 text-white/65">“{{ $story['quote'] }}”</p>
                            </article>
                        @endforeach
                    </div>
                    <button type="button" class="hidden items-center justify-center rounded-full border border-white/10 bg-white/[.03] text-white/70 transition hover:bg-white/[.07] hover:text-white lg:flex">
                        <i data-lucide="arrow-right" class="h-5 w-5"></i>
                    </button>
                </div>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <span class="h-2.5 w-2.5 rounded-full bg-violet-300"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-white/35"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-white/35"></span>
                </div>
            </section>

            <section id="pricing" class="section-shell cta-landscape relative mt-7 overflow-hidden px-6 py-8 sm:px-8 lg:px-10 lg:py-10">
                <div class="mountain-layer"></div>
                <div class="mountain-layer layer-2"></div>
                <div class="relative z-10">
                    <h2 class="text-center text-[2rem] font-extrabold uppercase tracking-[-.03em] text-white sm:text-[3.1rem]">
                        Sẵn sàng kích hoạt năng lượng tử của bạn?
                    </h2>
                    <p class="mx-auto mt-4 max-w-[820px] text-center text-[1rem] leading-8 text-white/70 sm:text-lg">
                        Hãy bắt đầu hành trình chuyển hóa và nâng cấp bản thân ngay hôm nay.
                    </p>

                    <div class="mx-auto mt-8 grid max-w-[1040px] gap-4 md:grid-cols-3">
                        @foreach ([
                            ['icon' => 'shield', 'title' => 'An toàn & bảo mật', 'desc' => 'Thông tin cá nhân được bảo vệ tuyệt đối'],
                            ['icon' => 'headphones', 'title' => 'Trải nghiệm cá nhân hóa', 'desc' => 'Cá nhân hóa theo trạng thái và nhu cầu của bạn'],
                            ['icon' => 'infinity', 'title' => 'Truy cập mọi lúc', 'desc' => 'Trải nghiệm không giới hạn mọi lúc, mọi nơi'],
                        ] as $point)
                            <div class="glass-panel rounded-[26px] px-5 py-5 text-center">
                                <div class="mx-auto grid h-14 w-14 place-items-center rounded-full border border-white/12 bg-white/[.04] text-[#ebb3ff]">
                                    <i data-lucide="{{ $point['icon'] }}" class="h-6 w-6"></i>
                                </div>
                                <p class="mt-4 text-lg font-bold text-white">{{ $point['title'] }}</p>
                                <p class="mt-2 text-sm leading-7 text-white/60">{{ $point['desc'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div id="guide" class="mt-9 flex flex-col items-center gap-4">
                        <a href="{{ $primaryCtaUrl }}" class="nebula-button inline-flex w-full max-w-[390px] items-center justify-center gap-3 rounded-2xl px-8 py-5 text-lg font-extrabold uppercase tracking-[.06em] text-white transition hover:translate-y-[-1px]">
                            Kích hoạt ngay
                            <i data-lucide="rocket" class="h-5 w-5"></i>
                        </a>
                        <div class="text-center text-sm leading-7 text-white/55">
                            {{ auth()->check() ? 'Tiếp tục ngay từ tài khoản hiện tại của bạn.' : 'Đăng ký trong vài bước và bắt đầu trải nghiệm ngay.' }}
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        window.addEventListener('load', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
