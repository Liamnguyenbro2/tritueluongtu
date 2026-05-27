@extends('layouts.app')

@section('content')
<section class="relative overflow-hidden rounded-[36px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-10 lg:p-14">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_75%_20%,rgba(139,92,246,.5),transparent_32%),radial-gradient(circle_at_18%_70%,rgba(248,200,78,.18),transparent_28%)]"></div>
    <div class="relative grid items-center gap-10 xl:grid-cols-[1.05fr_.95fr]">
        <div>
            <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-violet-300/20 bg-violet-400/10 px-4 py-2 text-xs font-semibold uppercase tracking-[.24em] text-violet-100">
                <i data-lucide="sparkles" class="h-4 w-4"></i> Quantum Intelligence
            </div>
            <h1 class="max-w-4xl text-5xl font-black tracking-tight sm:text-7xl">Năng Lượng Tích Cực</h1>
            <p class="mt-6 max-w-2xl text-lg leading-9 text-slate-300">Một nền tảng SaaS cao cấp cho thư viện nội dung, nâng cấp gói, ví số dư, referral và quản trị thu nhập.</p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-6 py-4 text-sm font-black text-white shadow-glow transition hover:-translate-y-1 hover:shadow-[0_0_60px_rgba(139,92,246,.55)]" href="{{ route('register') }}">
                    <i data-lucide="rocket" class="h-5 w-5"></i> Bắt đầu miễn phí
                </a>
                <a class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/10 px-6 py-4 text-sm font-bold text-white transition hover:-translate-y-1 hover:bg-white/15" href="{{ route('login') }}">
                    <i data-lucide="log-in" class="h-5 w-5"></i> Đăng nhập
                </a>
            </div>
        </div>
        <div class="relative">
            <div class="absolute -inset-6 rounded-[40px] bg-violet-500/20 blur-3xl"></div>
            <div class="relative overflow-hidden rounded-[32px] border border-white/10 bg-black/30 shadow-2xl">
                <img src="https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=1200&q=80" alt="Quantum dashboard" class="h-[460px] w-full object-cover opacity-85">
                <div class="absolute inset-0 bg-gradient-to-t from-night via-night/20 to-transparent"></div>
                <div class="absolute bottom-5 left-5 right-5 rounded-[24px] border border-white/10 bg-white/10 p-5 backdrop-blur-2xl">
                    <p class="text-sm text-slate-300">Premium dashboard preview</p>
                    <p class="mt-1 text-2xl font-black">16 nội dung • Ví ledger • Webhook QR</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
