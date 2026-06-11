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

{{--
@if(session('device_login_conflict'))
    @php($deviceConflict = session('device_login_conflict'))
    <div class="fixed inset-0 z-[80] grid place-items-center bg-black/70 px-4 backdrop-blur-sm">
        <div class="glass max-w-lg rounded-[32px] p-6 shadow-glow sm:p-8">
            <div class="flex items-start gap-4">
                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-amber-400/20 text-amber-100">
                    <i data-lucide="smartphone-charging" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/80">Phiên đăng nhập đang hoạt động</p>
                    <h2 class="mt-2 text-2xl font-black leading-tight">Không thể đăng nhập thiết bị mới</h2>
                    <p class="mt-3 leading-7 text-slate-200">
                        {{ $deviceConflict['message'] }}
                    </p>
                    @if(!empty($deviceConflict['device_name']))
                        <p class="mt-2 text-sm text-slate-300">Thiết bị hiện tại: {{ $deviceConflict['device_name'] }}</p>
                    @endif
                    @if(!empty($deviceConflict['last_seen_at']))
                        <p class="mt-2 text-sm text-slate-400">Hoạt động gần nhất: {{ $deviceConflict['last_seen_at'] }}</p>
                    @endif
                </div>
            </div>
            <button type="button" class="mt-6 w-full rounded-2xl bg-gradient-to-r from-amber-400 to-violet-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1" onclick="this.closest('.fixed').remove()">
                Tôi đã hiểu
            </button>
        </div>
    </div>
@endif
--}}

{{--
@if(session('session_expired_notice'))
    @php($expiredNotice = session('session_expired_notice'))
    <div class="fixed inset-0 z-[80] grid place-items-center bg-black/70 px-4 backdrop-blur-sm">
        <div class="glass max-w-lg rounded-[32px] p-6 shadow-glow sm:p-8">
            <div class="flex items-start gap-4">
                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-violet-500/20 text-violet-100">
                    <i data-lucide="hourglass" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-violet-200/80">Phiên đăng nhập hết hạn</p>
                    <h2 class="mt-2 text-2xl font-black leading-tight">Vui lòng đăng nhập lại</h2>
                    <p class="mt-3 leading-7 text-slate-200">
                        {{ $expiredNotice['message'] }}
                    </p>
                </div>
            </div>
            <button type="button" class="mt-6 w-full rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1" onclick="this.closest('.fixed').remove()">
                Tôi đã hiểu
            </button>
        </div>
    </div>
@endif
--}}

@php($loginLock = session('login_lock'))
@if(session('status'))
    <div class="mx-auto mb-6 max-w-2xl rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
        {{ session('status') }}
    </div>
@endif
<section class="mx-auto grid max-w-6xl items-center gap-8 lg:grid-cols-[.9fr_1.1fr]">
    <div class="glass rounded-[32px] p-6 sm:p-8">
        <div class="mb-8">
            <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">Welcome back</p>
            <h1 class="mt-3 text-4xl font-black">Đăng nhập</h1>
            <p class="mt-3 text-slate-400">Tiếp tục hành trình năng lượng tích cực của bạn.</p>
        </div>

        @if($errors->any())
            <div class="mb-4 rounded-2xl border border-rose-400/20 bg-rose-500/10 px-4 py-3 text-sm leading-7 text-rose-100">
                {{ $errors->first() }}
            </div>
        @endif

        @if($loginLock)
            <div class="mb-4 rounded-2xl border border-amber-300/20 bg-amber-400/10 px-4 py-4 text-sm text-amber-50" data-login-lock data-until="{{ $loginLock['until'] }}">
                <p class="font-bold uppercase tracking-[.18em] text-amber-200/80">Đăng nhập tạm khóa</p>
                <p class="mt-2 leading-7">
                    Bạn đã nhập sai quá 5 lần. Vui lòng chờ
                    <span class="font-black text-white" data-login-lock-countdown>00:00</span>
                    để thử lại.
                </p>
            </div>
        @endif

        <form method="post" action="{{ route('login') }}" class="space-y-4" data-login-form>
            @csrf
            <input class="premium-input" name="login" placeholder="Email hoặc ID tài khoản" value="{{ old('login') }}" required>
            <div class="relative">
                <input class="premium-input pr-14" name="password" type="password" placeholder="Mật khẩu" required data-password-toggle-input>
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex w-14 items-center justify-center text-slate-400 transition hover:text-violet-200"
                    data-password-toggle-button
                    aria-label="Hiện mật khẩu"
                    aria-pressed="false"
                >
                    <svg data-password-eye-open class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg data-password-eye-closed class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m3 3 18 18"></path>
                        <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                        <path d="M9.88 5.09A10.94 10.94 0 0 1 12 4.91c5.05 0 9.27 3.11 10.94 7.5a10.78 10.78 0 0 1-4.16 5.09"></path>
                        <path d="M6.61 6.61A10.75 10.75 0 0 0 1.94 12a10.75 10.75 0 0 0 7.45 6.73"></path>
                        <path d="M14.12 14.12A3 3 0 0 1 9.88 9.88"></path>
                    </svg>
                </button>
            </div>
            <button class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1 disabled:cursor-not-allowed disabled:opacity-50" data-login-submit @disabled((bool) $loginLock)>
                <i data-lucide="log-in" class="h-5 w-5"></i> Đăng nhập
            </button>
        </form>
        <p class="mt-4 text-center text-sm text-slate-400">
            <a href="{{ route('password.forgot') }}" class="cursor-pointer font-medium text-violet-300 transition hover:text-fuchsia-300 hover:underline">
                Quên mật khẩu?
            </a>
        </p>
        <p class="mt-4 text-center text-sm text-slate-400">
            Chưa có tài khoản?
            <a href="{{ route('register') }}" class="ml-1 cursor-pointer font-medium text-violet-300 transition hover:text-fuchsia-300 hover:underline">
                Đăng ký ngay
            </a>
        </p>
    </div>
    <div class="hidden overflow-hidden rounded-[36px] border border-white/10 bg-white/[.06] shadow-glow backdrop-blur-2xl lg:block">
        <img src="https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=1200&q=80" alt="Meditation" class="h-[520px] w-full object-cover opacity-80">
    </div>
</section>

<script>
    (() => {
        const lockBox = document.querySelector('[data-login-lock]');
        const countdown = document.querySelector('[data-login-lock-countdown]');
        const submitButton = document.querySelector('[data-login-submit]');

        if (!lockBox || !countdown || !submitButton) {
            return;
        }

        const lockUntil = new Date(lockBox.dataset.until).getTime();

        const render = () => {
            const diff = Math.max(0, lockUntil - Date.now());

            if (diff <= 0) {
                countdown.textContent = '00:00';
                submitButton.disabled = false;
                lockBox.remove();
                return;
            }

            const totalSeconds = Math.ceil(diff / 1000);
            const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
            const seconds = String(totalSeconds % 60).padStart(2, '0');
            countdown.textContent = `${minutes}:${seconds}`;
            submitButton.disabled = true;
        };

        render();
        setInterval(render, 1000);
    })();

    (() => {
        const passwordInput = document.querySelector('[data-password-toggle-input]');
        const toggleButton = document.querySelector('[data-password-toggle-button]');
        const eyeOpen = document.querySelector('[data-password-eye-open]');
        const eyeClosed = document.querySelector('[data-password-eye-closed]');

        if (!passwordInput || !toggleButton || !eyeOpen || !eyeClosed) {
            return;
        }

        toggleButton.addEventListener('click', () => {
            const isVisible = passwordInput.type === 'text';
            passwordInput.type = isVisible ? 'password' : 'text';
            eyeOpen.classList.toggle('hidden', !isVisible);
            eyeClosed.classList.toggle('hidden', isVisible);
            toggleButton.setAttribute('aria-pressed', String(!isVisible));
            toggleButton.setAttribute('aria-label', isVisible ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
        });
    })();
</script>
@endsection
