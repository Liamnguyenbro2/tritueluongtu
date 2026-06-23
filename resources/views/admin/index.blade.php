@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(14,165,233,.2),transparent_30%),radial-gradient(circle_at_18%_72%,rgba(139,92,246,.35),transparent_30%)]"></div>
        <div class="relative">
            <p class="text-sm font-semibold uppercase tracking-[.24em] text-sky-200/80">Admin Command Center</p>
            <h1 class="mt-3 text-4xl font-black sm:text-6xl">Admin Dashboard</h1>
            <p class="mt-4 max-w-2xl text-slate-300">{!! html_entity_decode('Theo d&#245;i v&#237; h&#7879; th&#7889;ng, ng&#432;&#7901;i d&#249;ng, thanh to&#225;n v&#224; y&#234;u c&#7847;u r&#250;t ti&#7873;n trong m&#7897;t giao di&#7879;n cao c&#7845;p.') !!}</p>
        </div>
    </section>

    <section class="grid gap-5 md:grid-cols-3">
        @foreach($systemWallets as $wallet)
            <article class="glass rounded-[28px] p-5 transition hover:-translate-y-1 hover:shadow-glow">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-400">{{ $wallet->type }}</p>
                    <i data-lucide="vault" class="h-5 w-5 text-amber-200"></i>
                </div>
                <p class="mt-3 text-3xl font-black">{{ number_format($wallet->balance_vnd, 0, ',', '.') }} đ</p>
            </article>
        @endforeach
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/70">Notification Center</p>
                <h2 class="mt-2 text-2xl font-black">{!! html_entity_decode('Th&#244;ng b&#225;o dashboard') !!}</h2>
                <p class="mt-1 text-sm text-slate-400">{!! html_entity_decode('T&#7841;o th&#244;ng b&#225;o c&#7889; &#273;&#7883;nh v&#224; th&#244;ng b&#225;o theo &#273;&#7907;t &#273;&#7875; user &#273;&#7885;c, x&#225;c nh&#7853;n v&#224; l&#432;u l&#7841;i l&#7883;ch s&#7917;.') !!}</p>
            </div>
            <a href="{{ route('admin.notifications.index') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-amber-300 to-violet-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                <i data-lucide="megaphone" class="h-5 w-5"></i> {!! html_entity_decode('Qu&#7843;n l&#253; th&#244;ng b&#225;o') !!}
            </a>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-5 flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/70">Branding</p>
                <h2 class="mt-2 text-2xl font-black">{!! html_entity_decode('Logo v&#224; text th&#432;&#417;ng hi&#7879;u') !!}</h2>
                <p class="mt-1 text-sm text-slate-400">{!! html_entity_decode('C&#7853;p nh&#7853;t logo v&#224; 2 d&#242;ng ch&#7919; hi&#7875;n th&#7883; &#7903; sidebar.') !!}</p>
            </div>
            <div class="flex items-center gap-3 rounded-[24px] border border-white/10 bg-black/25 p-3">
                @if(!empty($brandSettings['logo_url']))
                    <img src="{{ $brandSettings['logo_url'] }}" alt="{{ $brandSettings['name'] }}" class="h-12 w-12 rounded-2xl object-cover">
                @else
                    <div class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-amber-300 via-fuchsia-400 to-violet-600 shadow-glow">
                        <i data-lucide="sparkles" class="h-6 w-6 text-white"></i>
                    </div>
                @endif
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[.24em] text-amber-200/80">{{ $brandSettings['eyebrow'] }}</p>
                    <p class="text-lg font-bold">{{ $brandSettings['name'] }}</p>
                </div>
            </div>
        </div>

        <form method="post" action="{{ route('admin.branding.update') }}" enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-[1.2fr_.7fr_.7fr_auto]">
            @csrf
            @method('put')
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">URL logo</span>
                <input class="premium-input" name="brand_logo_url" value="{{ old('brand_logo_url', $brandSettings['logo_url']) }}" placeholder="{!! html_entity_decode('https://... ho&#7863;c &#273;&#7875; tr&#7889;ng d&#249;ng icon m&#7863;c &#273;&#7883;nh') !!}">
                <span class="text-xs text-slate-500">{!! html_entity_decode('Ho&#7863;c t&#7843;i &#7843;nh logo m&#7899;i b&#234;n d&#432;&#7899;i.') !!}</span>
                <input class="premium-input" name="brand_logo_file" type="file" accept="image/*">
            </label>
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">{!! html_entity_decode('D&#242;ng ch&#7919; nh&#7887;') !!}</span>
                <input class="premium-input" name="brand_eyebrow" value="{{ old('brand_eyebrow', $brandSettings['eyebrow']) }}" required>
            </label>
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">{!! html_entity_decode('T&#234;n th&#432;&#417;ng hi&#7879;u') !!}</span>
                <input class="premium-input" name="brand_name" value="{{ old('brand_name', $brandSettings['name']) }}" required>
            </label>
            <div class="flex items-end">
                <button class="w-full rounded-2xl bg-gradient-to-r from-amber-300 to-violet-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                    {!! html_entity_decode('L&#432;u th&#432;&#417;ng hi&#7879;u') !!}
                </button>
            </div>
        </form>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-5 flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-emerald-200/70">Payment Account</p>
                <h2 class="mt-2 text-2xl font-black">Tài khoản nhận thanh toán</h2>
                <p class="mt-1 text-sm text-slate-400">Mọi mã VietQR và thông tin chuyển khoản đều sử dụng cấu hình này.</p>
            </div>
            <div class="rounded-[24px] border border-emerald-300/20 bg-emerald-400/10 px-5 py-4">
                <p class="text-xs uppercase tracking-[.18em] text-emerald-200/70">Đang sử dụng</p>
                <p class="mt-2 font-black text-emerald-100">{{ $paymentSettings['bank_name'] ?: 'Chưa cấu hình' }}</p>
                <p class="mt-1 font-mono text-sm text-slate-300">{{ $paymentSettings['account_no'] ?: '-' }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $paymentSettings['account_name'] ?: '-' }}</p>
            </div>
        </div>

        <form method="post" action="{{ route('admin.payment-settings.update') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @csrf
            @method('put')
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">Ngân hàng</span>
                <input class="premium-input" name="payment_bank_name" value="{{ old('payment_bank_name', $paymentSettings['bank_name']) }}" placeholder="Tên ngân hàng" required>
                @error('payment_bank_name')<span class="text-xs text-rose-300">{{ $message }}</span>@enderror
            </label>
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">Mã ngân hàng VietQR</span>
                <input class="premium-input uppercase" name="payment_bank_code" value="{{ old('payment_bank_code', $paymentSettings['bank_code']) }}" placeholder="Mã ngân hàng VietQR" required>
                @error('payment_bank_code')<span class="text-xs text-rose-300">{{ $message }}</span>@enderror
            </label>
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">Số tài khoản</span>
                <input class="premium-input font-mono" name="payment_account_no" inputmode="numeric" value="{{ old('payment_account_no', $paymentSettings['account_no']) }}" placeholder="Số tài khoản" required>
                @error('payment_account_no')<span class="text-xs text-rose-300">{{ $message }}</span>@enderror
            </label>
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">Tên chủ tài khoản</span>
                <input class="premium-input" name="payment_account_name" value="{{ old('payment_account_name', $paymentSettings['account_name']) }}" placeholder="Tên chủ tài khoản" required>
                @error('payment_account_name')<span class="text-xs text-rose-300">{{ $message }}</span>@enderror
            </label>
            <div class="md:col-span-2 xl:col-span-4 flex justify-end">
                <button class="rounded-2xl bg-gradient-to-r from-emerald-400 to-cyan-500 px-6 py-4 font-black text-night shadow-glow transition hover:-translate-y-1">
                    Lưu cấu hình thanh toán
                </button>
            </div>
        </form>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.1fr_.9fr]">
        <div class="glass rounded-[32px] p-6">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-cyan-200/70">Accountant Access</p>
                    <h2 class="mt-2 text-2xl font-black">{!! html_entity_decode('T&#7841;o t&#224;i kho&#7843;n k&#7871; to&#225;n') !!}</h2>
                    <p class="mt-1 text-sm text-slate-400">{!! html_entity_decode('Admin c&#243; th&#7875; t&#7841;o m&#7899;i t&#224;i kho&#7843;n role') !!} <span class="font-semibold text-white">Accountant</span> {!! html_entity_decode('&#273;&#7875; &#273;&#259;ng nh&#7853;p v&#224;o Financial Dashboard ri&#234;ng.') !!}</p>
                </div>
                <i data-lucide="briefcase-business" class="h-6 w-6 text-cyan-200"></i>
            </div>

            <form method="post" action="{{ route('admin.accountants.store') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">Username</span>
                    <input class="premium-input" name="username" value="{{ old('username') }}" required>
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">{!! html_entity_decode('H&#7885; t&#234;n') !!}</span>
                    <input class="premium-input" name="name" value="{{ old('name') }}" required>
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">Email</span>
                    <input class="premium-input" name="email" type="email" value="{{ old('email') }}" required>
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">{!! html_entity_decode('S&#7889; &#273;i&#7879;n tho&#7841;i') !!}</span>
                    <input class="premium-input" name="phone" value="{{ old('phone') }}" required>
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">{!! html_entity_decode('M&#7853;t kh&#7849;u') !!}</span>
                    <input class="premium-input" name="password" type="password" required>
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">{!! html_entity_decode('X&#225;c nh&#7853;n m&#7853;t kh&#7849;u') !!}</span>
                    <input class="premium-input" name="password_confirmation" type="password" required>
                </label>
                <div class="md:col-span-2 flex justify-end">
                    <button class="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                        {!! html_entity_decode('T&#7841;o t&#224;i kho&#7843;n k&#7871; to&#225;n') !!}
                    </button>
                </div>
            </form>
        </div>

        <div class="glass rounded-[32px] p-6">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-cyan-200/70">Accountant List</p>
                    <h2 class="mt-2 text-2xl font-black">{!! html_entity_decode('T&#224;i kho&#7843;n k&#7871; to&#225;n hi&#7879;n c&#243;') !!}</h2>
                    <p class="mt-1 text-sm text-slate-400">{!! html_entity_decode('Theo d&#245;i nhanh c&#225;c email &#273;&#227; &#273;&#432;&#7907;c c&#7845;p quy&#7873;n accountant.') !!}</p>
                </div>
                <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-bold text-slate-200">
                    {{ $accountants->count() }} {!! html_entity_decode('t&#224;i kho&#7843;n') !!}
                </span>
            </div>

            <div class="space-y-3">
                @forelse($accountants as $accountant)
                    <div class="rounded-[24px] border border-white/10 bg-black/20 p-4">
                        <p class="font-semibold text-white">{{ $accountant->name }}</p>
                        <p class="mt-1 text-sm text-slate-400">{{ $accountant->email }}</p>
                        <p class="mt-2 text-xs uppercase tracking-[.18em] text-cyan-200/80">{{ $accountant->username }}</p>
                    </div>
                @empty
                    <div class="rounded-[24px] border border-dashed border-white/10 bg-black/20 p-6 text-sm text-slate-400">
                        {!! html_entity_decode('Ch&#432;a c&#243; t&#224;i kho&#7843;n k&#7871; to&#225;n n&#224;o.') !!}
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <div class="glass rounded-[32px] p-6">
            <div class="mb-5 flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-2xl font-black">{!! html_entity_decode('User g&#7847;n &#273;&#226;y') !!}</h2>
                    <p class="mt-1 text-sm text-slate-400">{!! html_entity_decode('T&#236;m theo email ho&#7863;c ID t&#224;i kho&#7843;n.') !!}</p>
                </div>
                <form method="get" action="{{ route('admin.index') }}" class="flex w-full gap-2 lg:w-[420px]">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                        <input class="premium-input pl-11" name="q" value="{{ $search }}" placeholder="{!! html_entity_decode('Email ho&#7863;c ID t&#224;i kho&#7843;n') !!}">
                    </div>
                    <button class="rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-3 font-bold shadow-glow transition hover:-translate-y-0.5">{!! html_entity_decode('T&#236;m') !!}</button>
                    @if($search !== '')
                        <a href="{{ route('admin.index') }}" class="grid place-items-center rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-bold text-slate-200 transition hover:bg-white/15">{!! html_entity_decode('X&#243;a') !!}</a>
                    @endif
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr><th class="py-3">ID</th><th>{!! html_entity_decode('ID t&#224;i kho&#7843;n') !!}</th><th>Email</th><th>Report</th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                    @forelse($users as $user)
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4">#{{ $user->id }}</td>
                            <td class="font-semibold text-white">{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td><a class="rounded-xl bg-violet-400/10 px-3 py-2 text-violet-100 transition hover:bg-violet-400/20" href="{{ route('admin.users.report', $user) }}">Xem</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-slate-400">{!! html_entity_decode('Kh&#244;ng t&#236;m th&#7845;y user ph&#249; h&#7907;p.') !!}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="mt-5">
                    {{ $users->onEachSide(1)->links() }}
                </div>
            @endif
        </div>

        <div class="glass rounded-[32px] p-6">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-violet-200/70">Wallet Transfer</p>
                    <h2 class="mt-2 text-2xl font-black">{!! html_entity_decode('N&#7841;p s&#7889; d&#432; v&#237; cho user') !!}</h2>
                    <p class="mt-1 text-sm text-slate-400">{!! html_entity_decode('T&#236;m user b&#7857;ng email ho&#7863;c s&#7889; &#273;i&#7879;n tho&#7841;i, sau &#273;&#243; chuy&#7875;n t&#7915; v&#237; admin.') !!}</p>
                </div>
                <i data-lucide="send" class="h-6 w-6 text-emerald-200"></i>
            </div>

            <div class="mb-5 rounded-[24px] border border-white/10 bg-black/20 p-4">
                <p class="text-sm text-slate-400">{!! html_entity_decode('S&#7889; d&#432; v&#237; admin c&#243; th&#7875; chuy&#7875;n') !!}</p>
                <p class="mt-1 text-3xl font-black">{{ number_format($adminWallet->balance_vnd, 0, ',', '.') }} đ</p>
            </div>

            @if($search === '')
                <div class="rounded-[24px] border border-amber-200/20 bg-amber-300/10 p-4 text-sm leading-6 text-amber-100">
                    {!! html_entity_decode('Nh&#7853;p email ho&#7863;c s&#7889; &#273;i&#7879;n tho&#7841;i &#7903; &#244; t&#236;m ki&#7871;m &#273;&#7875; ch&#7885;n user nh&#7853;n ti&#7873;n.') !!}
                </div>
            @else
                <form method="post" action="{{ route('admin.wallet-transfer') }}" class="grid gap-4">
                    @csrf
                    <label class="grid gap-2">
                        <span class="text-sm text-slate-400">{!! html_entity_decode('User nh&#7853;n ti&#7873;n') !!}</span>
                        <select class="premium-input" name="user_id" required>
                            <option value="">{!! html_entity_decode('Ch&#7885;n user') !!}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">
                                    #{{ $user->id }} - {{ $user->name }} - {{ $user->email }} - {{ $user->phone }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-2">
                        <span class="text-sm text-slate-400">{!! html_entity_decode('S&#7889; ti&#7873;n chuy&#7875;n') !!}</span>
                        <input class="premium-input" name="amount_vnd" type="text" inputmode="numeric" autocomplete="off" pattern="[0-9.]*" data-currency-input value="{{ old('amount_vnd') }}" placeholder="{!! html_entity_decode('T&#7889;i &#273;a') !!} {{ number_format($adminWallet->balance_vnd, 0, ',', '.') }} đ" required>
                    </label>
                    <button class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1">
                        {!! html_entity_decode('X&#225;c nh&#7853;n chuy&#7875;n') !!}
                    </button>
                </form>
            @endif

            <div class="mt-6">
                <h3 class="text-lg font-black">{!! html_entity_decode('L&#7883;ch s&#7917; chuy&#7875;n &#273;i g&#7847;n &#273;&#226;y') !!}</h3>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full min-w-[520px] text-left text-sm">
                        <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                        <tr><th class="py-3">{!! html_entity_decode('Th&#7901;i gian') !!}</th><th>{!! html_entity_decode('S&#7889; ti&#7873;n') !!}</th><th>{!! html_entity_decode('Ghi ch&#250;') !!}</th></tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                        @forelse($transferLogs as $entry)
                            <tr class="text-slate-300">
                                <td class="py-4">{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-rose-200">{{ number_format($entry->amount_vnd, 0, ',', '.') }} đ</td>
                                <td>{{ $entry->memoWithTimestamp() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-slate-400">{!! html_entity_decode('Ch&#432;a c&#243; l&#7883;ch s&#7917; chuy&#7875;n v&#237;.') !!}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="glass rounded-[32px] p-6">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-2xl font-black">{!! html_entity_decode('R&#250;t ti&#7873;n') !!}</h2>
                <i data-lucide="banknote-arrow-down" class="h-6 w-6 text-emerald-200"></i>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr><th class="py-3">{!! html_entity_decode('ID t&#224;i kho&#7843;n') !!}</th><th>Email</th><th>{!! html_entity_decode('S&#7889; ti&#7873;n') !!}</th><th>{!! html_entity_decode('Tr&#7841;ng th&#225;i') !!}</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                    @foreach($withdrawals as $withdrawal)
                        @php
                            $statusLabel = match ($withdrawal->status) {
                                'approved' => html_entity_decode('&#272;&#227; duy&#7879;t'),
                                'rejected' => html_entity_decode('T&#7915; ch&#7889;i'),
                                default => html_entity_decode('Ch&#7901; duy&#7879;t'),
                            };
                            $statusClass = match ($withdrawal->status) {
                                'approved' => 'bg-emerald-400/10 text-emerald-100',
                                'rejected' => 'bg-rose-400/10 text-rose-100',
                                default => 'bg-amber-300/10 text-amber-100',
                            };
                        @endphp
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4">#{{ $withdrawal->user_id }}</td>
                            <td>{{ $withdrawal->user?->email ?? html_entity_decode('Kh&#244;ng x&#225;c &#273;&#7883;nh') }}</td>
                            <td>{{ number_format($withdrawal->amount_vnd, 0, ',', '.') }} đ</td>
                            <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>
                                @if($withdrawal->status === 'pending')
                                    <div class="flex gap-2">
                                        <form method="post" action="{{ route('admin.withdrawals.approve', $withdrawal) }}">
                                            @csrf
                                            <button class="rounded-xl bg-emerald-400/10 px-3 py-2 text-emerald-100 transition hover:bg-emerald-400/20">{!! html_entity_decode('Duy&#7879;t') !!}</button>
                                        </form>
                                        <button
                                            type="button"
                                            data-reject-withdrawal
                                            data-reject-action="{{ route('admin.withdrawals.reject', $withdrawal) }}"
                                            data-reject-user="#{{ $withdrawal->user_id }} - {{ $withdrawal->user?->email ?? html_entity_decode('Kh&#244;ng x&#225;c &#273;&#7883;nh') }}"
                                            data-reject-amount="{{ number_format($withdrawal->amount_vnd, 0, ',', '.').' đ' }}"
                                            class="rounded-xl bg-rose-400/10 px-3 py-2 text-rose-100 transition hover:bg-rose-400/20"
                                        >
                                            {!! html_entity_decode('T&#7915; ch&#7889;i') !!}
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs font-semibold text-slate-500">{!! html_entity_decode('&#272;&#227; x&#7917; l&#253;') !!}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div id="withdraw-reject-modal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/75 p-4 backdrop-blur-xl">
                <form method="post" action="" class="glass w-full max-w-lg rounded-[32px] p-6">
                    @csrf
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[.22em] text-rose-200/80">{!! html_entity_decode('T&#7915; ch&#7889;i r&#250;t ti&#7873;n') !!}</p>
                            <h3 class="mt-2 text-2xl font-black">{!! html_entity_decode('Nh&#7853;p l&#253; do t&#7915; ch&#7889;i') !!}</h3>
                        </div>
                        <button type="button" class="grid h-11 w-11 place-items-center rounded-2xl bg-white/10 transition hover:bg-white/20" data-reject-close>
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>

                    <div class="mt-5 rounded-[24px] border border-rose-300/20 bg-rose-500/10 p-4 text-sm leading-6 text-rose-100">
                        {!! html_entity_decode('H&#7879; th&#7889;ng s&#7869; t&#7915; ch&#7889;i y&#234;u c&#7847;u v&#224; ho&#224;n l&#7841;i s&#7889; ti&#7873;n &#273;&#227; t&#7841;m gi&#7919; v&#224;o v&#237; s&#7889; d&#432; c&#7911;a user.') !!}
                    </div>

                    <div class="mt-5 grid gap-3 rounded-[24px] border border-white/10 bg-black/25 p-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-400">User</span>
                            <span class="text-right font-semibold text-white" data-reject-user></span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-400">{!! html_entity_decode('S&#7889; ti&#7873;n') !!}</span>
                            <span class="font-semibold text-white" data-reject-amount></span>
                        </div>
                    </div>

                    <label class="mt-5 grid gap-2">
                        <span class="text-sm text-slate-400">{!! html_entity_decode('L&#253; do t&#7915; ch&#7889;i') !!}</span>
                        <textarea class="premium-input min-h-28" name="admin_note" required placeholder="{!! html_entity_decode('Nh&#7853;p l&#253; do &#273;&#7875; user bi&#7871;t v&#236; sao y&#234;u c&#7847;u b&#7883; t&#7915; ch&#7889;i') !!}"></textarea>
                    </label>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <button type="button" class="rounded-2xl border border-white/10 bg-white/10 px-5 py-3 font-bold transition hover:bg-white/15" data-reject-close>
                            {!! html_entity_decode('H&#7911;y') !!}
                        </button>
                        <button class="rounded-2xl bg-gradient-to-r from-rose-500 to-violet-500 px-5 py-3 font-black shadow-glow transition hover:-translate-y-1">
                            {!! html_entity_decode('X&#225;c nh&#7853;n t&#7915; ch&#7889;i') !!}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
    (() => {
        const sharedPoolCard = Array.from(document.querySelectorAll('section.grid.gap-5.md\\:grid-cols-3 > article'))
            .find((card) => card.textContent.includes('shared_pool'));

        if (sharedPoolCard) {
            sharedPoolCard.classList.add('cursor-pointer');
            sharedPoolCard.addEventListener('click', () => {
                window.location.href = @js(route('admin.shared-pool.history'));
            });

            const hint = document.createElement('p');
            hint.className = 'mt-2 text-xs uppercase tracking-[.2em] text-sky-200/70';
            hint.textContent = 'Xem lịch sử dòng chia';
            sharedPoolCard.appendChild(hint);
        }

        const modal = document.getElementById('withdraw-reject-modal');
        if (!modal) return;
        const form = modal.querySelector('form');
        const userField = modal.querySelector('[data-reject-user]');
        const amountField = modal.querySelector('[data-reject-amount]');

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('grid');
        };

        document.querySelectorAll('[data-reject-withdrawal]').forEach((button) => {
            button.addEventListener('click', () => {
                form.action = button.dataset.rejectAction || '';
                userField.textContent = button.dataset.rejectUser || '';
                amountField.textContent = button.dataset.rejectAmount || '';
                modal.classList.remove('hidden');
                modal.classList.add('grid');
            });
        });

        modal.querySelectorAll('[data-reject-close]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    })();

    document.querySelectorAll('[data-currency-input]').forEach((input) => {
        const formatVnd = () => {
            const digits = input.value.replace(/\D/g, '');
            input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        };

        input.addEventListener('input', formatVnd);
        input.addEventListener('paste', () => requestAnimationFrame(formatVnd));
        formatVnd();
    });
</script>
@endsection
