<div class="fixed inset-0 z-[85] flex items-center justify-center bg-black/80 px-4 py-8 backdrop-blur-sm">
    <div class="glass w-full max-w-2xl rounded-[32px] p-6 shadow-glow sm:p-8">
        <div class="flex items-start gap-4">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-amber-300/20 text-amber-100">
                <i data-lucide="shield-check" class="h-6 w-6"></i>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/80">KYC b&#7855;t bu&#7897;c</p>
                <h2 class="mt-2 text-2xl font-black leading-tight">Ho&#224;n th&#224;nh KYC tr&#432;&#7899;c khi ti&#7871;p t&#7909;c s&#7917; d&#7909;ng d&#7883;ch v&#7909; tr&#7843; ph&#237;</h2>
                <p class="mt-3 leading-7 text-slate-200">
                    Sau khi thanh to&#225;n th&#224;nh c&#244;ng, b&#7841;n c&#7847;n c&#7853;p nh&#7853;t th&#244;ng tin KYC theo &#273;&#250;ng CCCD &#273;&#7875; ti&#7871;p t&#7909;c s&#7917; d&#7909;ng c&#225;c t&#237;nh n&#259;ng tr&#7843; ph&#237;.
                </p>
            </div>
        </div>

        <form method="post" action="{{ route('kyc.store') }}" class="mt-6 grid gap-4">
            @csrf
            <label class="grid gap-2">
                <span class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400">H&#7884; V&#192; T&#202;N (THEO CCCD)</span>
                <input
                    class="premium-input"
                    name="full_name"
                    value="{{ old('full_name', $kycRecord?->full_name ?? auth()->user()->name) }}"
                    placeholder="Nguy&#7877;n V&#259;n A"
                    required
                >
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400">S&#7888; CCCD</span>
                <input
                    class="premium-input"
                    name="citizen_id"
                    value="{{ old('citizen_id', $kycRecord?->citizen_id) }}"
                    inputmode="numeric"
                    pattern="[0-9]+"
                    placeholder="Nh&#7853;p s&#7889; CCCD"
                    required
                >
            </label>

            <label class="grid gap-2">
                <span class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400">&#272;&#7882;A CH&#7880; TH&#431;&#7900;NG TR&#218; (THEO CCCD)</span>
                <textarea
                    class="premium-input min-h-28 resize-y"
                    name="address"
                    placeholder="Nh&#7853;p &#273;&#7883;a ch&#7881; th&#432;&#7901;ng tr&#250; theo CCCD"
                    required
                >{{ old('address', $kycRecord?->address) }}</textarea>
            </label>

            <div class="rounded-2xl border border-amber-300/15 bg-amber-300/10 px-4 py-3 text-sm leading-6 text-amber-50">
                Th&#244;ng tin ph&#7843;i tr&#249;ng kh&#7899;p v&#7899;i CCCD. B&#7841;n kh&#244;ng th&#7875; ti&#7871;p t&#7909;c s&#7917; d&#7909;ng d&#7883;ch v&#7909; tr&#7843; ph&#237; n&#7871;u ch&#432;a g&#7917;i KYC.
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('kyc.index') }}" class="text-sm font-semibold text-violet-200 transition hover:text-white">
                    Xem trang KYC chi ti&#7871;t
                </a>
                <button class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                    <i data-lucide="send" class="h-5 w-5"></i>
                    G&#7917;i th&#244;ng tin
                </button>
            </div>
        </form>
    </div>
</div>
