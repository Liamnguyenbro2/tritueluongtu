@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6 sm:p-8">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-cyan-200/70">KYC Control</p>
                <h1 class="mt-3 text-4xl font-black sm:text-5xl">KYC t&#224;i kho&#7843;n</h1>
                <p class="mt-3 max-w-3xl text-slate-400">
                    C&#7853;p nh&#7853;t th&#244;ng tin &#273;&#7883;nh danh theo CCCD &#273;&#7875; ph&#7909;c v&#7909; &#273;&#7889;i so&#225;t d&#7919; li&#7879;u v&#224; duy tr&#236; quy&#7873;n s&#7917; d&#7909;ng c&#225;c d&#7883;ch v&#7909; tr&#7843; ph&#237;.
                </p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 px-5 py-4">
                <p class="text-sm text-slate-400">Tr&#7841;ng th&#225;i KYC</p>
                <p class="mt-2 text-2xl font-black {{ $kyc ? 'text-emerald-200' : 'text-amber-200' }}">
                    {!! $kyc ? '&#272;&#227; c&#7853;p nh&#7853;t' : 'Ch&#432;a c&#7853;p nh&#7853;t' !!}
                </p>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.1fr_.9fr]">
        <div class="glass rounded-[32px] p-6">
            <div class="mb-5">
                <h2 class="text-2xl font-black">Bi&#7875;u m&#7851;u KYC</h2>
                <p class="mt-2 text-sm leading-6 text-slate-400">
                    T&#7845;t c&#7843; th&#244;ng tin b&#7855;t bu&#7897;c ph&#7843;i tr&#249;ng kh&#7899;p v&#7899;i CCCD.
                </p>
            </div>

            <form method="post" action="{{ route('kyc.store') }}" class="grid gap-4">
                @csrf
                <label class="grid gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400">H&#7884; V&#192; T&#202;N (THEO CCCD)</span>
                    <input class="premium-input" name="full_name" value="{{ old('full_name', $kyc?->full_name ?? auth()->user()->name) }}" required>
                </label>

                <label class="grid gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400">S&#7888; CCCD</span>
                    <input class="premium-input" name="citizen_id" value="{{ old('citizen_id', $kyc?->citizen_id) }}" inputmode="numeric" pattern="[0-9]+" required>
                </label>

                <label class="grid gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400">&#272;&#7882;A CH&#7880; TH&#431;&#7900;NG TR&#218; (THEO CCCD)</span>
                    <textarea class="premium-input min-h-32 resize-y" name="address" required>{{ old('address', $kyc?->address) }}</textarea>
                </label>

                <button class="mt-2 inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                    <i data-lucide="save" class="h-5 w-5"></i>
                    G&#7917;i th&#244;ng tin
                </button>
            </form>
        </div>

        <div class="space-y-6">
            <section class="glass rounded-[32px] p-6">
                <h2 class="text-xl font-black">H&#432;&#7899;ng d&#7851;n KYC</h2>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-300">
                    <li class="flex gap-3">
                        <i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 text-emerald-300"></i>
                        <span>Kh&#244;ng &#273;&#7875; tr&#7889;ng b&#7845;t k&#7923; tr&#432;&#7901;ng n&#224;o.</span>
                    </li>
                    <li class="flex gap-3">
                        <i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 text-emerald-300"></i>
                        <span>S&#7889; CCCD ch&#7881; &#273;&#432;&#7907;c nh&#7853;p s&#7889;.</span>
                    </li>
                    <li class="flex gap-3">
                        <i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 text-emerald-300"></i>
                        <span>Th&#244;ng tin ph&#7843;i tr&#249;ng kh&#7899;p v&#7899;i CCCD &#273;&#7875; ph&#7909;c v&#7909; nghi&#7879;p v&#7909; v&#224; &#273;&#7889;i so&#225;t sau n&#224;y.</span>
                    </li>
                </ul>
            </section>

            <section class="glass rounded-[32px] p-6">
                <h2 class="text-xl font-black">L&#7847;n g&#7917;i g&#7847;n nh&#7845;t</h2>
                @if($kyc)
                    <div class="mt-4 space-y-3 text-sm text-slate-300">
                        <p><span class="text-slate-500">H&#7885; t&#234;n:</span> {{ $kyc->full_name }}</p>
                        <p><span class="text-slate-500">S&#7889; CCCD:</span> {{ $kyc->citizen_id }}</p>
                        <p><span class="text-slate-500">&#272;&#7883;a ch&#7881;:</span> {{ $kyc->address }}</p>
                        <p><span class="text-slate-500">Ng&#224;y g&#7917;i:</span> {{ $kyc->submitted_at?->format('d/m/Y H:i') }}</p>
                    </div>
                @else
                    <p class="mt-4 text-sm text-slate-400">B&#7841;n ch&#432;a g&#7917;i th&#244;ng tin KYC.</p>
                @endif
            </section>
        </div>
    </section>
</div>
@endsection
