@extends('layouts.app')

@section('content')
@php
    $copy = [
        'title' => html_entity_decode('C&#7845;u h&#236;nh Email OTP'),
        'intro' => html_entity_decode('C&#7845;u h&#236;nh Gmail SMTP v&#224; ch&#7881;nh s&#7917;a m&#7851;u email OTP qu&#234;n m&#7853;t kh&#7849;u tr&#7921;c ti&#7871;p tr&#234;n website. H&#7879; th&#7889;ng ch&#7881; d&#249;ng c&#7845;u h&#236;nh n&#224;y cho ch&#7913;c n&#259;ng kh&#244;i ph&#7909;c m&#7853;t kh&#7849;u.'),
        'smtp_status' => html_entity_decode('Tr&#7841;ng th&#225;i SMTP'),
        'active' => html_entity_decode('&#272;ang ho&#7841;t &#273;&#7897;ng'),
        'inactive' => html_entity_decode('Ch&#432;a k&#237;ch ho&#7841;t'),
        'current_sender' => html_entity_decode('Email g&#7917;i &#273;i hi&#7879;n t&#7841;i'),
        'not_configured' => html_entity_decode('Ch&#432;a c&#7845;u h&#236;nh'),
        'smtp_title' => html_entity_decode('Gmail SMTP cho OTP'),
        'smtp_intro' => html_entity_decode('Laravel s&#7869; &#273;&#7885;c c&#7845;u h&#236;nh t&#7915; database v&#224; n&#7841;p runtime b&#7857;ng Config::set(), kh&#244;ng ch&#7881;nh file .env.'),
        'app_password_hint' => html_entity_decode('Nh&#7853;p App Password Gmail'),
        'saved_password' => html_entity_decode('&#272;ang l&#432;u'),
        'blank_password' => html_entity_decode('&#272;&#7875; tr&#7889;ng n&#7871;u kh&#244;ng mu&#7889;n thay &#273;&#7893;i.'),
        'activate_config' => html_entity_decode('K&#237;ch ho&#7841;t c&#7845;u h&#236;nh n&#224;y'),
        'activate_note' => html_entity_decode('N&#7871;u t&#7855;t, h&#7879; th&#7889;ng s&#7869; kh&#244;ng g&#7917;i OTP qu&#234;n m&#7853;t kh&#7849;u.'),
        'save_config' => html_entity_decode('L&#432;u c&#7845;u h&#236;nh'),
        'test_connection' => html_entity_decode('Ki&#7875;m tra k&#7871;t n&#7889;i SMTP'),
        'test_recipient' => html_entity_decode('Email nh&#7853;n th&#7917; nghi&#7879;m'),
        'send_test' => html_entity_decode('G&#7917;i Email ki&#7875;m tra'),
        'template_title' => html_entity_decode('M&#7851;u Email Qu&#234;n M&#7853;t Kh&#7849;u'),
        'template_intro' => html_entity_decode('Admin c&#243; th&#7875; ch&#7881;nh ti&#234;u &#273;&#7873; v&#224; n&#7897;i dung email OTP m&#224; kh&#244;ng c&#7847;n s&#7917;a code, nh&#432;ng b&#7855;t bu&#7897;c ph&#7843;i gi&#7919;'),
        'template_variables' => html_entity_decode('Bi&#7871;n c&#243; th&#7875; s&#7917; d&#7909;ng'),
        'save_template' => html_entity_decode('L&#432;u m&#7851;u Email'),
        'preview_email' => html_entity_decode('Xem tr&#432;&#7899;c Email'),
        'restore_default' => html_entity_decode('Kh&#244;i ph&#7909;c m&#7851;u m&#7863;c &#273;&#7883;nh'),
        'preview_title' => html_entity_decode('Xem tr&#432;&#7899;c Email'),
        'preview_subject_fallback' => html_entity_decode('Xem tr&#432;&#7899;c ti&#234;u &#273;&#7873; email'),
        'and_word' => html_entity_decode('v&#224;'),
        'email_subject' => html_entity_decode('Ti&#234;u &#273;&#7873; Email'),
        'email_content' => html_entity_decode('N&#7897;i dung Email'),
        'site_name' => html_entity_decode('Tr&#237; Tu&#7879; L&#432;&#7907;ng T&#7917;'),
    ];
@endphp
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_75%_18%,rgba(168,85,247,.22),transparent_30%),radial-gradient(circle_at_15%_78%,rgba(59,130,246,.18),transparent_30%)]"></div>
        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/80">Admin Console</p>
                <h1 class="mt-3 text-4xl font-black sm:text-6xl">{{ $copy['title'] }}</h1>
                <p class="mt-4 max-w-3xl text-slate-300">{{ $copy['intro'] }}</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-[24px] border border-white/10 bg-black/25 px-5 py-4">
                    <p class="text-xs text-slate-400">{{ $copy['smtp_status'] }}</p>
                    <p class="mt-1 text-lg font-black {{ $smtp?->is_active ? 'text-emerald-300' : 'text-amber-200' }}">
                        {{ $smtp?->is_active ? $copy['active'] : $copy['inactive'] }}
                    </p>
                </div>
                <div class="rounded-[24px] border border-white/10 bg-black/25 px-5 py-4">
                    <p class="text-xs text-slate-400">{{ $copy['current_sender'] }}</p>
                    <p class="mt-1 text-lg font-black text-white">{{ $smtp?->gmail_address ?: $copy['not_configured'] }}</p>
                </div>
            </div>
        </div>
    </section>

    @if(session('status'))
        <div class="rounded-[24px] border border-emerald-400/20 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-100">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-[24px] border border-rose-400/20 bg-rose-500/10 px-5 py-4 text-sm text-rose-100">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1.05fr_.95fr]">
        <section class="glass rounded-[32px] p-6">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-sky-200/70">SMTP</p>
                    <h2 class="mt-2 text-2xl font-black">{{ $copy['smtp_title'] }}</h2>
                    <p class="mt-1 text-sm text-slate-400">{{ $copy['smtp_intro'] }}</p>
                </div>
                <i data-lucide="mail-check" class="h-6 w-6 text-sky-200"></i>
            </div>

            <form method="post" action="{{ route('admin.email-otp.smtp.update') }}" class="grid gap-4">
                @csrf
                @method('put')
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="grid gap-2">
                        <span class="text-sm text-slate-400">Gmail Address</span>
                        <input class="premium-input" type="email" name="gmail_address" value="{{ old('gmail_address', $smtp?->gmail_address) }}" required>
                    </label>
                    <label class="grid gap-2">
                        <span class="text-sm text-slate-400">App Password</span>
                        <input class="premium-input" type="password" name="app_password" placeholder="{{ $smtp?->maskedPassword() ?: $copy['app_password_hint'] }}">
                        @if($smtp?->maskedPassword())
                            <span class="text-xs text-slate-500">{{ $copy['saved_password'] }}: {{ $smtp->maskedPassword() }}. {{ $copy['blank_password'] }}</span>
                        @endif
                    </label>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <label class="grid gap-2">
                        <span class="text-sm text-slate-400">SMTP Host</span>
                        <input class="premium-input" name="smtp_host" value="{{ old('smtp_host', $smtp?->smtp_host ?? 'smtp.gmail.com') }}" required>
                    </label>
                    <label class="grid gap-2">
                        <span class="text-sm text-slate-400">SMTP Port</span>
                        <input class="premium-input" type="number" name="smtp_port" value="{{ old('smtp_port', $smtp?->smtp_port ?? 587) }}" required>
                    </label>
                    <label class="grid gap-2">
                        <span class="text-sm text-slate-400">Encryption</span>
                        <select class="premium-input" name="encryption">
                            <option value="tls" @selected(old('encryption', $smtp?->encryption ?? 'tls') === 'tls')>TLS</option>
                        </select>
                    </label>
                </div>

                <label class="flex items-center justify-between gap-4 rounded-[24px] border border-white/10 bg-black/25 px-4 py-3">
                    <span>
                        <span class="block text-sm font-semibold text-white">{{ $copy['activate_config'] }}</span>
                        <span class="block text-xs text-slate-400">{{ $copy['activate_note'] }}</span>
                    </span>
                    <input type="checkbox" name="is_active" value="1" class="h-5 w-5 rounded border-white/20 bg-black/40 text-violet-500" @checked(old('is_active', $smtp?->is_active ?? true))>
                </label>

                <div class="flex flex-wrap gap-3">
                    <button class="rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                        {{ $copy['save_config'] }}
                    </button>
                </div>
            </form>

            <form method="post" action="{{ route('admin.email-otp.smtp.test') }}" class="mt-3">
                @csrf
                <button class="rounded-2xl border border-white/10 bg-white/10 px-5 py-4 font-black text-slate-100 transition hover:-translate-y-1 hover:bg-white/15">
                    {{ $copy['test_connection'] }}
                </button>
            </form>

            <form method="post" action="{{ route('admin.email-otp.smtp.send-test') }}" class="mt-5 grid gap-3 rounded-[28px] border border-white/10 bg-black/20 p-4">
                @csrf
                <div class="grid gap-3 md:grid-cols-[1fr_auto]">
                    <label class="grid gap-2">
                        <span class="text-sm text-slate-400">{{ $copy['test_recipient'] }}</span>
                        <input class="premium-input" type="email" name="test_email" value="{{ old('test_email', $smtp?->gmail_address) }}" required>
                    </label>
                    <button class="rounded-2xl bg-gradient-to-r from-sky-500 to-violet-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1 md:self-end">
                        {{ $copy['send_test'] }}
                    </button>
                </div>
            </form>
        </section>

        <section class="glass rounded-[32px] p-6">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/70">Template</p>
                    <h2 class="mt-2 text-2xl font-black">{{ $copy['template_title'] }}</h2>
                    <p class="mt-1 text-sm text-slate-400">{{ $copy['template_intro'] }} <code>@{{otp}}</code> {{ $copy['and_word'] }} <code>@{{expire_minutes}}</code>.</p>
                </div>
                <i data-lucide="file-pen-line" class="h-6 w-6 text-amber-200"></i>
            </div>

            <form method="post" action="{{ route('admin.email-otp.template.update') }}" class="grid gap-4">
                @csrf
                @method('put')
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">{{ $copy['email_subject'] }}</span>
                    <input class="premium-input" name="subject" value="{{ old('subject', $template->subject) }}" required data-template-subject>
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">{{ $copy['email_content'] }}</span>
                    <textarea class="premium-input min-h-64" name="content" required data-template-content>{{ old('content', $template->content) }}</textarea>
                </label>
                <div class="rounded-[24px] border border-white/10 bg-black/20 p-4 text-sm text-slate-300">
                    <p class="font-semibold text-white">{{ $copy['template_variables'] }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($templateVariables as $variable)
                            <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-semibold text-violet-100">{{ $variable }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                        {{ $copy['save_template'] }}
                    </button>
                    <button type="button" class="rounded-2xl border border-white/10 bg-white/10 px-5 py-4 font-black text-slate-100 transition hover:-translate-y-1 hover:bg-white/15" data-template-preview-trigger>
                        {{ $copy['preview_email'] }}
                    </button>
                </div>
            </form>

            <form method="post" action="{{ route('admin.email-otp.template.restore') }}" class="mt-3">
                @csrf
                <button class="rounded-2xl border border-amber-300/20 bg-amber-400/10 px-5 py-4 font-black text-amber-50 transition hover:-translate-y-1 hover:bg-amber-400/15">
                    {{ $copy['restore_default'] }}
                </button>
            </form>

            <div id="email-preview" class="mt-6 rounded-[28px] border border-white/10 bg-slate-950/70 p-4 sm:p-6">
                <div class="rounded-[24px] border border-white/10 bg-slate-900 p-6 shadow-[0_0_30px_rgba(139,92,246,.14)]">
                    <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400">{{ $copy['preview_title'] }}</p>
                    <h3 class="mt-3 text-xl font-black text-white" data-preview-subject>{{ $preview['subject'] }}</h3>
                    <div class="mt-4 space-y-3 text-sm leading-7 text-slate-300" data-preview-content>{!! nl2br(e($preview['text'])) !!}</div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    (() => {
        const subjectInput = document.querySelector('[data-template-subject]');
        const contentInput = document.querySelector('[data-template-content]');
        const previewSubject = document.querySelector('[data-preview-subject]');
        const previewContent = document.querySelector('[data-preview-content]');
        const previewTrigger = document.querySelector('[data-template-preview-trigger]');
        const fallbackTitle = @json($copy['preview_subject_fallback']);
        const siteName = @json($copy['site_name']);

        if (!subjectInput || !contentInput || !previewSubject || !previewContent || !previewTrigger) {
            return;
        }

        const replaceVariables = (text) => {
            return text
                .replaceAll(@json('{{otp}}'), '123456')
                .replaceAll(@json('{{expire_minutes}}'), '5')
                .replaceAll(@json('{{site_name}}'), siteName)
                .replaceAll(@json('{{current_year}}'), String(new Date().getFullYear()));
        };

        const escapeHtml = (text) => {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        };

        const render = () => {
            previewSubject.textContent = replaceVariables(subjectInput.value.trim()) || fallbackTitle;
            previewContent.innerHTML = escapeHtml(replaceVariables(contentInput.value.trim())).replace(/\n/g, '<br>');
        };

        subjectInput.addEventListener('input', render);
        contentInput.addEventListener('input', render);
        previewTrigger.addEventListener('click', () => {
            render();
            document.querySelector('#email-preview')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });

        render();
    })();
</script>
@endsection
