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
        <form method="post" action="{{ route('register') }}" class="grid gap-4 sm:grid-cols-2" data-register-form>
            @csrf
            <label class="grid gap-2">
                <input
                    class="premium-input"
                    name="username"
                    inputmode="latin"
                    autocomplete="username"
                    pattern="[A-Za-z0-9._]{4,30}"
                    minlength="4"
                    maxlength="30"
                    placeholder="ID tài khoản"
                    value="{{ old('username') }}"
                    required
                    data-username-input
                    data-username-check-url="{{ route('register.username.lookup') }}"
                >
                <span class="hidden px-1 text-xs font-semibold text-slate-400 transition-colors duration-300" data-username-status></span>
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
                <input
                    class="premium-input"
                    name="email"
                    type="email"
                    autocomplete="email"
                    placeholder="Email"
                    value="{{ old('email') }}"
                    required
                    data-email-input
                    data-email-check-url="{{ route('register.email.lookup') }}"
                >
                <span class="hidden px-1 text-xs font-semibold text-slate-400 transition-colors duration-300" data-email-status></span>
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
                <input
                    class="premium-input"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Mật khẩu"
                    minlength="6"
                    required
                    data-password-input
                >
                @error('password')
                    <span class="px-1 text-xs font-semibold text-rose-200">{{ $message }}</span>
                @enderror
            </label>
            <label class="grid gap-2">
                <input
                    class="premium-input"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Nhập lại mật khẩu"
                    required
                    data-password-confirmation-input
                >
                <span
                    class="px-1 text-xs font-semibold text-slate-400 transition-colors duration-300"
                    data-password-confirmation-status
                >
                    Mật khẩu xác nhận không khớp
                </span>
            </label>
            <div class="rounded-3xl border border-white/10 bg-white/5 p-5 sm:col-span-2" data-password-feedback>
                <p class="text-xs font-semibold uppercase tracking-[.24em] text-violet-200/70">Mật khẩu an toàn</p>
                <p class="mt-3 text-sm text-slate-300">Mật khẩu phải thỏa mãn các điều kiện sau:</p>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    <li class="flex items-center gap-3 text-sm text-slate-400 transition-all duration-300" data-password-rule="length">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full border border-slate-500/70 transition-all duration-300" data-rule-icon>
                            <svg class="hidden h-3.5 w-3.5 text-emerald-300" data-rule-check viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.072 7.071a1 1 0 01-1.414 0L4.68 10.244a1 1 0 111.414-1.414l2.831 2.83 6.365-6.37a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>Độ dài tối thiểu 6 ký tự</span>
                    </li>
                    <li class="flex items-center gap-3 text-sm text-slate-400 transition-all duration-300" data-password-rule="uppercase">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full border border-slate-500/70 transition-all duration-300" data-rule-icon>
                            <svg class="hidden h-3.5 w-3.5 text-emerald-300" data-rule-check viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.072 7.071a1 1 0 01-1.414 0L4.68 10.244a1 1 0 111.414-1.414l2.831 2.83 6.365-6.37a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>Bao gồm ít nhất 1 chữ viết hoa (A-Z)</span>
                    </li>
                    <li class="flex items-center gap-3 text-sm text-slate-400 transition-all duration-300" data-password-rule="lowercase">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full border border-slate-500/70 transition-all duration-300" data-rule-icon>
                            <svg class="hidden h-3.5 w-3.5 text-emerald-300" data-rule-check viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.072 7.071a1 1 0 01-1.414 0L4.68 10.244a1 1 0 111.414-1.414l2.831 2.83 6.365-6.37a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>Bao gồm ít nhất 1 chữ viết thường (a-z)</span>
                    </li>
                    <li class="flex items-center gap-3 text-sm text-slate-400 transition-all duration-300" data-password-rule="number">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full border border-slate-500/70 transition-all duration-300" data-rule-icon>
                            <svg class="hidden h-3.5 w-3.5 text-emerald-300" data-rule-check viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.072 7.071a1 1 0 01-1.414 0L4.68 10.244a1 1 0 111.414-1.414l2.831 2.83 6.365-6.37a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>Bao gồm ít nhất 1 chữ số (0-9)</span>
                    </li>
                    <li class="flex items-center gap-3 text-sm text-slate-400 transition-all duration-300 sm:col-span-2" data-password-rule="symbol">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full border border-slate-500/70 transition-all duration-300" data-rule-icon>
                            <svg class="hidden h-3.5 w-3.5 text-emerald-300" data-rule-check viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.072 7.071a1 1 0 01-1.414 0L4.68 10.244a1 1 0 111.414-1.414l2.831 2.83 6.365-6.37a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>Bao gồm ít nhất 1 ký tự đặc biệt (!@#$%^&amp;*...)</span>
                    </li>
                </ul>
            </div>
            <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300 sm:col-span-2">
                <input class="h-5 w-5 rounded border-white/20 bg-black/40 text-violet-500" type="checkbox" name="accepted_terms" value="1" required>
                Tôi đồng ý điều khoản sử dụng.
            </label>
            @error('accepted_terms')
                <p class="px-1 text-xs font-semibold text-rose-200 sm:col-span-2">{{ $message }}</p>
            @enderror
            <button class="flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1 disabled:cursor-not-allowed disabled:opacity-60 sm:col-span-2" data-register-submit>
                <i data-lucide="user-plus" class="h-5 w-5"></i> Tạo tài khoản
            </button>
        </form>
        <p class="mt-4 text-center text-sm text-slate-400">
            Bạn đã có tài khoản?
            <a href="{{ route('login') }}" class="ml-1 cursor-pointer font-medium text-violet-300 transition hover:text-fuchsia-300 hover:underline">
                Đăng nhập
            </a>
        </p>
    </div>
</section>

<script>
    (() => {
        const form = document.querySelector('[data-register-form]');
        const submitButton = document.querySelector('[data-register-submit]');
        const usernameInput = document.querySelector('[data-username-input]');
        const usernameStatus = document.querySelector('[data-username-status]');
        const emailInput = document.querySelector('[data-email-input]');
        const emailStatus = document.querySelector('[data-email-status]');

        if (!form || !submitButton || !usernameInput || !usernameStatus || !emailInput || !emailStatus) {
            return;
        }

        let usernameState = 'idle';
        let emailState = 'idle';
        let usernameTimer = null;
        let emailTimer = null;
        let usernameController = null;
        let emailController = null;

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const usernamePattern = /^[a-z0-9._]{4,30}$/;
        const reservedUsernames = new Set([
            'admin',
            'administrator',
            'support',
            'root',
            'system',
            'mod',
            'moderator',
            'staff',
            'api',
            'login',
            'register',
            'dashboard',
        ]);

        const updateSubmitState = () => {
            const blockedStates = new Set(['checking', 'exists', 'reserved']);
            submitButton.disabled = blockedStates.has(usernameState) || blockedStates.has(emailState);
        };

        const paintStatus = (node, text, tone) => {
            node.textContent = text;
            node.classList.remove('hidden', 'text-slate-400', 'text-emerald-300', 'text-rose-300');

            if (tone === 'success') {
                node.classList.add('text-emerald-300');
            } else if (tone === 'error') {
                node.classList.add('text-rose-300');
            } else {
                node.classList.add('text-slate-400');
            }
        };

        const clearStatus = (node) => {
            node.textContent = '';
            node.classList.add('hidden');
            node.classList.remove('text-slate-400', 'text-emerald-300', 'text-rose-300');
        };

        const checkUsername = async () => {
            const username = usernameInput.value.trim().toLowerCase();
            usernameInput.value = username;
            usernameController?.abort();

            if (!username) {
                usernameState = 'idle';
                clearStatus(usernameStatus);
                updateSubmitState();
                return;
            }

            if (!usernamePattern.test(username)) {
                usernameState = 'invalid';
                paintStatus(usernameStatus, 'ID phải dài 4-30 ký tự và chỉ gồm chữ, số, dấu chấm hoặc dấu gạch dưới.', 'error');
                updateSubmitState();
                return;
            }

            if (reservedUsernames.has(username)) {
                usernameState = 'reserved';
                paintStatus(usernameStatus, '❌ ID tài khoản này không được phép sử dụng', 'error');
                updateSubmitState();
                return;
            }

            usernameState = 'checking';
            paintStatus(usernameStatus, 'Đang kiểm tra...', 'muted');
            updateSubmitState();
            usernameController = new AbortController();

            try {
                const url = new URL(usernameInput.dataset.usernameCheckUrl, window.location.origin);
                url.searchParams.set('username', username);

                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: usernameController.signal,
                });
                const data = await response.json();

                if (data.exists) {
                    usernameState = 'exists';
                    paintStatus(usernameStatus, '❌ ID tài khoản đã tồn tại', 'error');
                } else {
                    usernameState = 'available';
                    paintStatus(usernameStatus, '✅ ID tài khoản có thể sử dụng', 'success');
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    usernameState = 'error';
                    paintStatus(usernameStatus, 'Không thể kiểm tra ID tài khoản lúc này.', 'error');
                }
            }

            updateSubmitState();
        };

        const checkEmail = async () => {
            const email = emailInput.value.trim().toLowerCase();
            emailInput.value = email;
            emailController?.abort();

            if (!email) {
                emailState = 'idle';
                clearStatus(emailStatus);
                updateSubmitState();
                return;
            }

            if (!emailPattern.test(email)) {
                emailState = 'invalid';
                paintStatus(emailStatus, 'Email chưa đúng định dạng.', 'error');
                updateSubmitState();
                return;
            }

            emailState = 'checking';
            paintStatus(emailStatus, 'Đang kiểm tra...', 'muted');
            updateSubmitState();
            emailController = new AbortController();

            try {
                const url = new URL(emailInput.dataset.emailCheckUrl, window.location.origin);
                url.searchParams.set('email', email);

                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: emailController.signal,
                });
                const data = await response.json();

                if (data.exists) {
                    emailState = 'exists';
                    paintStatus(emailStatus, '❌ Email đã tồn tại', 'error');
                } else {
                    emailState = 'available';
                    paintStatus(emailStatus, '✅ Email có thể sử dụng', 'success');
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    emailState = 'error';
                    paintStatus(emailStatus, 'Không thể kiểm tra email lúc này.', 'error');
                }
            }

            updateSubmitState();
        };

        usernameInput.addEventListener('input', () => {
            window.clearTimeout(usernameTimer);
            usernameTimer = window.setTimeout(checkUsername, 500);
        });

        usernameInput.addEventListener('blur', checkUsername);

        emailInput.addEventListener('input', () => {
            window.clearTimeout(emailTimer);
            emailTimer = window.setTimeout(checkEmail, 500);
        });

        emailInput.addEventListener('blur', checkEmail);

        form.addEventListener('submit', (event) => {
            if (new Set(['checking', 'exists', 'reserved']).has(usernameState)) {
                event.preventDefault();
                paintStatus(usernameStatus, 'ID tài khoản đã được sử dụng.', 'error');
            }

            if (new Set(['checking', 'exists']).has(emailState)) {
                event.preventDefault();
                paintStatus(emailStatus, 'Email này đã được sử dụng.', 'error');
            }

            updateSubmitState();
        });

        checkUsername();
        checkEmail();
    })();

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

    (() => {
        const passwordInput = document.querySelector('[data-password-input]');
        const confirmationInput = document.querySelector('[data-password-confirmation-input]');
        const confirmationStatus = document.querySelector('[data-password-confirmation-status]');

        if (!passwordInput || !confirmationInput || !confirmationStatus) {
            return;
        }

        const rules = {
            length: (value) => value.length >= 6,
            uppercase: (value) => /[A-Z]/.test(value),
            lowercase: (value) => /[a-z]/.test(value),
            number: (value) => /\d/.test(value),
            symbol: (value) => /[^A-Za-z0-9]/.test(value),
        };

        const ruleNodes = Object.fromEntries(
            Array.from(document.querySelectorAll('[data-password-rule]')).map((node) => [node.dataset.passwordRule, node])
        );

        const setRuleState = (node, passed) => {
            const icon = node.querySelector('[data-rule-icon]');
            const check = node.querySelector('[data-rule-check]');

            node.classList.toggle('text-emerald-300', passed);
            node.classList.toggle('text-slate-400', !passed);
            icon.classList.toggle('border-emerald-400/80', passed);
            icon.classList.toggle('bg-emerald-500/15', passed);
            icon.classList.toggle('border-slate-500/70', !passed);
            icon.classList.toggle('bg-transparent', !passed);
            check.classList.toggle('hidden', !passed);
        };

        const setConfirmationState = () => {
            const hasConfirmation = confirmationInput.value.length > 0;
            const passed = hasConfirmation && confirmationInput.value === passwordInput.value;

            confirmationStatus.textContent = passed
                ? 'Mật khẩu xác nhận khớp'
                : 'Mật khẩu xác nhận không khớp';

            confirmationStatus.classList.toggle('text-emerald-300', passed);
            confirmationStatus.classList.toggle('text-slate-400', !passed);
        };

        const updatePasswordState = () => {
            const value = passwordInput.value;

            Object.entries(rules).forEach(([name, test]) => {
                const node = ruleNodes[name];
                if (!node) {
                    return;
                }

                setRuleState(node, test(value));
            });

            setConfirmationState();
        };

        passwordInput.addEventListener('input', updatePasswordState);
        confirmationInput.addEventListener('input', setConfirmationState);
        confirmationInput.addEventListener('blur', setConfirmationState);

        updatePasswordState();
    })();
</script>
@endsection
