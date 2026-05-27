@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-3xl">
    <div class="glass rounded-[32px] p-6 sm:p-8">
        <div class="mb-8 flex items-start justify-between gap-6">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">Create account</p>
                <h1 class="mt-3 text-4xl font-black">Đăng ký tài khoản</h1>
                <p class="mt-3 text-slate-400">Nhận 3 nội dung miễn phí trong 48 giờ sau khi kích hoạt.</p>
            </div>
            <div class="hidden rounded-3xl bg-amber-300/10 p-4 text-amber-100 sm:block">
                <i data-lucide="gift" class="h-8 w-8"></i>
            </div>
        </div>
        <form method="post" action="{{ route('register') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            <label class="grid gap-2">
                <input class="premium-input" name="username" inputmode="latin" autocomplete="username" pattern="[A-Za-z0-9]+" maxlength="50" placeholder="ID tài khoản" value="{{ old('username') }}" required>
                @error('username')
                    <span class="px-1 text-xs font-semibold text-rose-200">{{ $message }}</span>
                @enderror
            </label>
            <label class="grid gap-2">
                <input class="premium-input" name="name" autocomplete="name" maxlength="100" placeholder="Họ tên" value="{{ old('name') }}" required>
                @error('name')
                    <span class="px-1 text-xs font-semibold text-rose-200">{{ $message }}</span>
                @enderror
            </label>
            <label class="grid gap-2">
                <input class="premium-input" name="email" type="email" autocomplete="email" placeholder="Email" value="{{ old('email') }}" required>
                @error('email')
                    <span class="px-1 text-xs font-semibold text-rose-200">{{ $message }}</span>
                @enderror
            </label>
            <label class="grid gap-2">
                <input class="premium-input" name="phone" type="tel" inputmode="numeric" autocomplete="tel" pattern="[0-9]{10}" maxlength="10" placeholder="Số điện thoại" value="{{ old('phone') }}" required>
                @error('phone')
                    <span class="px-1 text-xs font-semibold text-rose-200">{{ $message }}</span>
                @enderror
            </label>
            <label class="grid gap-2 sm:col-span-2">
                <input class="premium-input uppercase" name="referral_code" placeholder="Mã giới thiệu" value="{{ old('referral_code', request('ref')) }}" data-referral-code data-referral-url="{{ route('register.referral.lookup') }}">
                <span class="hidden px-1 text-xs font-semibold" data-referral-message></span>
                @error('referral_code')
                    <span class="px-1 text-xs font-semibold text-rose-200">{{ $message }}</span>
                @enderror
            </label>
            <label class="grid gap-2">
                <input class="premium-input" name="password" type="password" autocomplete="new-password" placeholder="Mật khẩu" required>
                @error('password')
                    <span class="px-1 text-xs font-semibold text-rose-200">{{ $message }}</span>
                @enderror
            </label>
            <label class="grid gap-2">
                <input class="premium-input" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Nhập lại mật khẩu" required>
            </label>
            <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300 sm:col-span-2">
                <input class="h-5 w-5 rounded border-white/20 bg-black/40 text-violet-500" type="checkbox" name="accepted_terms" value="1" required>
                Tôi đồng ý điều khoản sử dụng.
            </label>
            @error('accepted_terms')
                <p class="px-1 text-xs font-semibold text-rose-200 sm:col-span-2">{{ $message }}</p>
            @enderror
            <button class="flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1 sm:col-span-2">
                <i data-lucide="user-plus" class="h-5 w-5"></i> Tạo tài khoản
            </button>
        </form>
    </div>
</section>

<script>
    (() => {
        const input = document.querySelector('[data-referral-code]');
        const message = document.querySelector('[data-referral-message]');
        if (!input || !message) return;

        let timer = null;
        let controller = null;

        const setMessage = (text, ok = false) => {
            message.textContent = text;
            message.classList.remove('hidden', 'text-emerald-200', 'text-rose-200', 'text-slate-400');
            message.classList.add(ok ? 'text-emerald-200' : 'text-rose-200');
        };

        const clearMessage = () => {
            message.textContent = '';
            message.classList.add('hidden');
            message.classList.remove('text-emerald-200', 'text-rose-200', 'text-slate-400');
        };

        const lookup = async () => {
            const code = input.value.trim().toUpperCase();
            input.value = code;
            controller?.abort();

            if (!code) {
                clearMessage();
                return;
            }

            controller = new AbortController();

            try {
                const url = new URL(input.dataset.referralUrl, window.location.origin);
                url.searchParams.set('code', code);
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                const data = await response.json();

                if (data.found && data.name) {
                    setMessage(`Người giới thiệu: ${data.name}`, true);
                    return;
                }

                setMessage('Không tìm thấy mã giới thiệu.');
            } catch (error) {
                if (error.name !== 'AbortError') {
                    setMessage('Chưa kiểm tra được mã giới thiệu.');
                }
            }
        };

        input.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(lookup, 250);
        });

        lookup();
    })();
</script>
@endsection
