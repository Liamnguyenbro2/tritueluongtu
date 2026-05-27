@extends('layouts.app')

@section('content')
@if(session('suspension_notice'))
    @php($notice = session('suspension_notice'))
    <div class="fixed inset-0 z-[80] grid place-items-center bg-black/70 px-4 backdrop-blur-sm">
        <div class="glass max-w-lg rounded-[32px] p-6 shadow-glow sm:p-8">
            <div class="flex items-start gap-4">
                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-rose-500/20 text-rose-100">
                    <i data-lucide="shield-alert" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-rose-200/80">Tài khoản bị khóa</p>
                    <h2 class="mt-2 text-2xl font-black leading-tight">Không thể đăng nhập</h2>
                    <p class="mt-3 leading-7 text-slate-200">
                        Tài khoản #{{ $notice['user_id'] }} - {{ $notice['email'] }} đang khóa {{ $notice['type_label'] }}.
                    </p>
                    <p class="mt-2 leading-7 text-slate-300">Lý do: {{ $notice['reason'] }}</p>
                    @if(!empty($notice['ends_at']))
                        <p class="mt-2 text-sm text-slate-400">Hết hạn khóa: {{ $notice['ends_at'] }}</p>
                    @endif
                </div>
            </div>
            <button type="button" class="mt-6 w-full rounded-2xl bg-gradient-to-r from-rose-500 to-violet-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1" onclick="this.closest('.fixed').remove()">
                Tôi đã hiểu
            </button>
        </div>
    </div>
@endif

<section class="mx-auto grid max-w-6xl items-center gap-8 lg:grid-cols-[.9fr_1.1fr]">
    <div class="glass rounded-[32px] p-6 sm:p-8">
        <div class="mb-8">
            <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">Welcome back</p>
            <h1 class="mt-3 text-4xl font-black">Đăng nhập</h1>
            <p class="mt-3 text-slate-400">Tiếp tục hành trình năng lượng tích cực của bạn.</p>
        </div>
        <form method="post" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <input class="premium-input" name="login" placeholder="Email hoặc ID tài khoản" value="{{ old('login') }}" required>
            <input class="premium-input" name="password" type="password" placeholder="Mật khẩu" required>
            <button class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1">
                <i data-lucide="log-in" class="h-5 w-5"></i> Đăng nhập
            </button>
        </form>
    </div>
    <div class="hidden overflow-hidden rounded-[36px] border border-white/10 bg-white/[.06] shadow-glow backdrop-blur-2xl lg:block">
        <img src="https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=1200&q=80" alt="Meditation" class="h-[520px] w-full object-cover opacity-80">
    </div>
</section>
@endsection
