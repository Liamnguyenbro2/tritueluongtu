@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-5xl">
    <div class="grid gap-8 lg:grid-cols-[.92fr_1.08fr]">
        <div class="glass rounded-[32px] p-6 sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">Password Recovery</p>
            <div class="mt-4 flex items-center gap-3 text-xs font-semibold uppercase tracking-[.18em] text-slate-400">
                <span class="{{ $step === 'email' ? 'text-violet-200' : '' }}">Bước 1/3</span>
                <span class="h-px flex-1 bg-white/10"></span>
                <span class="{{ $step === 'otp' ? 'text-violet-200' : '' }}">Bước 2/3</span>
                <span class="h-px flex-1 bg-white/10"></span>
                <span class="{{ $step === 'reset' || $step === 'done' ? 'text-violet-200' : '' }}">Bước 3/3</span>
            </div>

            @if(session('status'))
                <div class="mt-6 rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mt-6 rounded-2xl border border-rose-400/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                    {{ $errors->first() }}
                </div>
            @endif

            @if($step === 'email')
                <div class="mt-8">
                    <h1 class="text-4xl font-black">Forgot Password</h1>
                    <p class="mt-3 leading-7 text-slate-400">Nhập email tài khoản để nhận mã OTP khôi phục mật khẩu.</p>

                    <form method="post" action="{{ route('password.forgot.send-otp') }}" class="mt-6 space-y-4">
                        @csrf
                        <label class="grid gap-2">
                            <span class="text-sm text-slate-400">Email tài khoản</span>
                            <input class="premium-input" type="email" name="email" value="{{ old('email', $email) }}" placeholder="name@example.com" required>
                        </label>
                        <button class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1">
                            <i data-lucide="send" class="h-5 w-5"></i> Gửi OTP
                        </button>
                    </form>
                </div>
            @elseif($step === 'otp')
                <div class="mt-8">
                    <h1 class="text-4xl font-black">Verify OTP</h1>
                    <p class="mt-3 leading-7 text-slate-400">Chúng tôi đã gửi mã xác thực tới email của bạn.</p>
                    <p class="mt-2 text-sm text-slate-500">OTP có hiệu lực trong {{ $otpExpireMinutes }} phút và chỉ sử dụng một lần.</p>

                    <form method="post" action="{{ route('password.forgot.verify-otp') }}" class="mt-6 space-y-4">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <label class="grid gap-2">
                            <span class="text-sm text-slate-400">Mã OTP 6 số</span>
                            <input class="premium-input text-center text-2xl tracking-[.5em]" type="text" name="otp" maxlength="6" inputmode="numeric" placeholder="123456" required>
                        </label>
                        <button class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1">
                            <i data-lucide="shield-check" class="h-5 w-5"></i> Xác thực OTP
                        </button>
                    </form>

                    <form method="post" action="{{ route('password.forgot.send-otp') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <button class="flex w-full items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/10 px-5 py-4 font-black text-slate-100 transition hover:-translate-y-1 hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50" data-resend-button @disabled($resendSeconds > 0)>
                            <i data-lucide="refresh-ccw" class="h-5 w-5"></i>
                            <span data-resend-label>{{ $resendSeconds > 0 ? "Gửi lại OTP sau {$resendSeconds}s" : 'Gửi lại OTP' }}</span>
                        </button>
                    </form>
                </div>
            @elseif($step === 'reset')
                <div class="mt-8">
                    <h1 class="text-4xl font-black">Reset Password</h1>
                    <p class="mt-3 leading-7 text-slate-400">OTP đã hợp lệ. Hãy nhập mật khẩu mới cho tài khoản của bạn.</p>

                    <form method="post" action="{{ route('password.forgot.reset') }}" class="mt-6 space-y-4">
                        @csrf
                        <label class="grid gap-2">
                            <span class="text-sm text-slate-400">Mật khẩu mới</span>
                            <input class="premium-input" type="password" name="password" placeholder="Mật khẩu mới" required>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-sm text-slate-400">Nhập lại mật khẩu mới</span>
                            <input class="premium-input" type="password" name="password_confirmation" placeholder="Xác nhận mật khẩu mới" required>
                        </label>
                        <button class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-400 to-sky-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                            <i data-lucide="key-round" class="h-5 w-5"></i> Đặt mật khẩu mới
                        </button>
                    </form>
                </div>
            @else
                <div class="mt-8">
                    <h1 class="text-4xl font-black">Thành công</h1>
                    <p class="mt-3 leading-7 text-slate-400">Mật khẩu của bạn đã được cập nhật thành công. Bạn có thể đăng nhập lại ngay.</p>
                    <a href="{{ route('login') }}" class="mt-6 flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1">
                        <i data-lucide="log-in" class="h-5 w-5"></i> Đăng nhập ngay
                    </a>
                </div>
            @endif

            @if($step !== 'done')
                <p class="mt-4 text-center text-sm text-slate-400">
                    Đã nhớ mật khẩu?
                    <a href="{{ route('login') }}" class="ml-1 font-medium text-violet-300 transition hover:text-fuchsia-300 hover:underline">
                        Quay lại đăng nhập
                    </a>
                </p>
            @endif
        </div>

        <div class="hidden overflow-hidden rounded-[36px] border border-white/10 bg-white/[.06] shadow-glow backdrop-blur-2xl lg:block">
            <div class="flex h-full flex-col justify-between bg-[radial-gradient(circle_at_50%_18%,rgba(192,132,252,.35),transparent_28%),linear-gradient(180deg,#0f172a,#111827)] p-8">
                <div class="rounded-full border border-violet-300/20 bg-violet-400/10 px-4 py-2 text-sm font-semibold uppercase tracking-[.22em] text-violet-100">
                    OTP Email
                </div>
                <div class="space-y-5">
                    <h2 class="text-4xl font-black leading-tight text-white">Khôi phục mật khẩu an toàn bằng Gmail SMTP</h2>
                    <p class="max-w-xl leading-8 text-slate-300">OTP 6 số sẽ được gửi trực tiếp tới email đăng ký, hết hạn sau 5 phút, chỉ dùng được một lần và có giới hạn chống spam theo email và IP.</p>
                    <div class="grid gap-3">
                        <div class="rounded-[24px] border border-white/10 bg-black/20 px-5 py-4 text-slate-200">1. Nhập email tài khoản</div>
                        <div class="rounded-[24px] border border-white/10 bg-black/20 px-5 py-4 text-slate-200">2. Nhận OTP và xác thực</div>
                        <div class="rounded-[24px] border border-white/10 bg-black/20 px-5 py-4 text-slate-200">3. Tạo mật khẩu mới</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@if($step === 'otp')
<script>
    (() => {
        const resendButton = document.querySelector('[data-resend-button]');
        const resendLabel = document.querySelector('[data-resend-label]');
        const secondsInitial = {{ (int) $resendSeconds }};

        if (!resendButton || !resendLabel || secondsInitial <= 0) {
            return;
        }

        let secondsRemaining = secondsInitial;

        const render = () => {
            if (secondsRemaining <= 0) {
                resendButton.disabled = false;
                resendLabel.textContent = 'Gửi lại OTP';
                return;
            }

            resendButton.disabled = true;
            resendLabel.textContent = `Gửi lại OTP sau ${secondsRemaining}s`;
            secondsRemaining -= 1;
        };

        render();
        const timer = setInterval(() => {
            render();

            if (secondsRemaining < 0) {
                clearInterval(timer);
            }
        }, 1000);
    })();
</script>
@endif
@endsection
