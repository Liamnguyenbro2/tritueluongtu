@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_10%,rgba(248,200,78,.2),transparent_30%),radial-gradient(circle_at_20%_80%,rgba(139,92,246,.36),transparent_34%)]"></div>
        <div class="relative flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-amber-200/80">Upgrade Studio</p>
                <h1 class="mt-3 text-4xl font-black sm:text-6xl">N&#226;ng c&#7845;p g&#243;i</h1>
                <p class="mt-4 max-w-2xl text-slate-300">
                    Thanh to&#225;n b&#7857;ng QR ng&#226;n h&#224;ng ho&#7863;c d&#249;ng v&#237; s&#7889; d&#432; khi t&#224;i kho&#7843;n c&#243; &#273;&#7911; ti&#7873;n.
                    G&#243;i th&#225;ng gi&#7901; y&#234;u c&#7847;u ch&#7885;n tr&#7921;c ti&#7871;p b&#224;i h&#7885;c mu&#7889;n m&#7903; kh&#243;a ngay khi thanh to&#225;n.
                </p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 p-5">
                <p class="text-sm text-slate-400">S&#7889; d&#432; v&#237;</p>
                <p class="mt-1 text-2xl font-black">{{ number_format($wallet->balance_vnd, 0, ',', '.') }} &#273;</p>
            </div>
        </div>
    </section>

    <section class="grid gap-5 lg:grid-cols-2">
        @foreach($plans as $plan)
            @php
                $hasBankQr = $plan->bank_qr_enabled;
                $hasWallet = $plan->wallet_enabled;
                $canPayWithWallet = $hasWallet && $wallet->balance_vnd >= $plan->price_vnd;
                $qrImageUrl = $plan->bankQrImageUrl();
                $qrDownloadUrl = $plan->bankQrImageDownloadUrl();
                $isMonthly = $plan->code === config('quantum.plans.monthly_code');
                $monthlyLessonOptions = $isMonthly
                    ? collect($lessons)
                        ->reject(fn ($lesson) => in_array($lesson->id, $unlockedLessonIds, true))
                        ->map(fn ($lesson) => [
                            'id' => (string) $lesson->id,
                            'price' => (int) $lesson->unlock_price_vnd,
                            'label' => str_pad((string) $lesson->position, 2, '0', STR_PAD_LEFT).' - '.$lesson->title,
                        ])
                        ->values()
                    : collect();
            @endphp

            <article
                class="group relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-2xl shadow-black/30 backdrop-blur-2xl transition duration-300 hover:-translate-y-2 hover:border-amber-200/40 hover:shadow-gold"
                @if($isMonthly)
                    x-data="{
                        selectedLessonId: @js((string) old('lesson_id', '')),
                        lessonOptions: @js($monthlyLessonOptions),
                        walletBalance: {{ (int) $wallet->balance_vnd }},
                        findLesson(id) {
                            return this.lessonOptions.find((lesson) => lesson.id === id) || null;
                        },
                        formatVnd(value) {
                            return new Intl.NumberFormat('vi-VN').format(value);
                        },
                        get selectedLesson() {
                            return this.findLesson(this.selectedLessonId);
                        },
                        get selectedPrice() {
                            return this.selectedLesson ? this.selectedLesson.price : 0;
                        },
                        get hasSelectedLesson() {
                            return !!this.selectedLesson;
                        },
                        get walletEnough() {
                            return this.hasSelectedLesson && this.walletBalance >= this.selectedPrice;
                        },
                    }"
                @endif
            >
                <div class="absolute -right-16 -top-16 h-44 w-44 rounded-full bg-violet-500/30 blur-3xl transition group-hover:bg-amber-300/25"></div>
                <div class="relative">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div class="grid h-14 w-14 place-items-center rounded-3xl bg-gradient-to-br from-amber-300 to-violet-500 shadow-gold">
                            <i data-lucide="{{ $plan->code === 'yearly' ? 'crown' : 'calendar-days' }}" class="h-7 w-7 text-white"></i>
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-2 text-[11px] font-black uppercase tracking-[.18em]">
                            @if($plan->code === 'yearly')
                                <span class="rounded-full bg-amber-300 px-3 py-1 text-night">Best value</span>
                            @endif
                            <span class="rounded-full px-3 py-1 {{ $hasBankQr ? 'bg-violet-500/20 text-violet-100' : 'bg-white/5 text-slate-500' }}">
                                QR {{ $hasBankQr ? 'ON' : 'OFF' }}
                            </span>
                            <span class="rounded-full px-3 py-1 {{ $hasWallet ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-slate-500' }}">
                                VI {{ $hasWallet ? 'ON' : 'OFF' }}
                            </span>
                        </div>
                    </div>

                    <h2 class="text-3xl font-black">{{ $plan->name }}</h2>
                    @if($isMonthly)
                        <div class="mt-4">
                            <p class="bg-gradient-to-r from-white to-violet-200 bg-clip-text text-5xl font-black text-transparent" x-text="hasSelectedLesson ? `${formatVnd(selectedPrice)} đ` : 'Chọn bài học'"></p>
                            <p class="mt-2 text-sm text-slate-400" x-text="hasSelectedLesson ? `Thanh toán đúng giá của ${selectedLesson.label}` : 'Chọn bài học để xem đúng giá cần thanh toán.'"></p>
                        </div>
                    @else
                        <p class="mt-4 bg-gradient-to-r from-white to-violet-200 bg-clip-text text-5xl font-black text-transparent">{{ number_format($plan->price_vnd, 0, ',', '.') }} d</p>
                    @endif
                    <p class="mt-2 text-slate-400">{{ $plan->duration_days }} ng&#224;y s&#7917; d&#7909;ng</p>

                    @if($plan->description)
                        <p class="mt-4 rounded-2xl border border-white/10 bg-black/15 px-4 py-3 text-sm leading-6 text-slate-300">
                            {{ $plan->description }}
                        </p>
                    @endif

                    <ul class="mt-6 space-y-3 text-sm text-slate-300">
                        @foreach($plan->billingFeatures() as $feature)
                            <li class="flex items-center gap-3">
                                <i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-300"></i>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    @if($isMonthly)
                        <div class="mt-6 rounded-[28px] border border-amber-200/15 bg-black/20 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-white">Ch&#7885;n b&#224;i h&#7885;c m&#7903; tr&#7921;c ti&#7871;p</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-400">
                                        Danh s&#225;ch l&#7845;y theo t&#234;n b&#224;i h&#7885;c hi&#7879;n c&#243; trong th&#432; vi&#7879;n v&#224; c&#7853;p nh&#7853;t theo d&#7919; li&#7879;u m&#7899;i nh&#7845;t.
                                        Thanh to&#225;n xong s&#7869; m&#7903; ngay b&#224;i h&#7885;c &#273;&#227; ch&#7885;n.
                                    </p>
                                </div>
                                <i data-lucide="list-video" class="mt-1 h-5 w-5 text-amber-200"></i>
                            </div>

                            <form method="post" action="{{ route('billing.orders.store') }}" class="mt-4 grid gap-3">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                                <label class="grid gap-2">
                                    <span class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400">T&#202;N B&#192;I H&#7884;C</span>
                                    <select name="lesson_id" class="premium-input" x-model="selectedLessonId" required>
                                        <option value="">Ch&#7885;n b&#224;i h&#7885;c mu&#7889;n m&#7903; kh&#243;a</option>
                                        @foreach($lessons as $lesson)
                                            @php
                                                $alreadyUnlocked = in_array($lesson->id, $unlockedLessonIds, true);
                                            @endphp
                                            <option value="{{ $lesson->id }}" {{ (string) old('lesson_id') === (string) $lesson->id ? 'selected' : '' }} {{ $alreadyUnlocked ? 'disabled' : '' }}>
                                                {{ str_pad((string) $lesson->position, 2, '0', STR_PAD_LEFT) }} - {{ $lesson->title }}
                                                @if($alreadyUnlocked)
                                                    (&#272;&#227; m&#7903; kh&#243;a)
                                                @else
                                                    ({{ number_format($lesson->unlock_price_vnd, 0, ',', '.') }} &#273;)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </label>

                                @error('lesson_id')
                                    <p class="text-sm font-medium text-rose-300">{{ $message }}</p>
                                @enderror

                                <div class="rounded-2xl border border-white/10 bg-white/[.03] px-4 py-3 text-xs leading-6 text-slate-400">
                                    G&#243;i th&#225;ng ch&#7881; duy tr&#236; th&#7901;i h&#7841;n 30 ng&#224;y. B&#224;i h&#7885;c &#273;&#227; m&#7903; v&#7851;n gi&#7919; c&#417; ch&#7871; b&#7853;t/t&#7855;t 7 ng&#224;y nh&#432; hi&#7879;n t&#7841;i.
                                </div>

                                <div class="rounded-2xl border border-emerald-300/15 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100" x-show="hasSelectedLesson" x-cloak>
                                    <p class="font-semibold">Số tiền thanh toán cho khóa đã chọn</p>
                                    <p class="mt-1 font-mono text-lg font-black" x-text="`${formatVnd(selectedPrice)} đ`"></p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    @if($hasBankQr)
                                        <button type="submit" name="payment_method" value="bank_qr" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!hasSelectedLesson">
                                            <i data-lucide="qr-code" class="h-5 w-5"></i> T&#7841;o QR m&#7903; kh&#243;a
                                        </button>
                                    @else
                                        <div class="flex items-center justify-center rounded-2xl border border-dashed border-white/10 bg-white/[.03] px-5 py-4 text-sm font-semibold text-slate-500">
                                            QR t&#7841;m t&#7855;t
                                        </div>
                                    @endif

                                    @if($hasWallet)
                                        <button
                                            type="submit"
                                            name="payment_method"
                                            value="wallet"
                                            class="flex w-full items-center justify-center gap-2 rounded-2xl border px-5 py-4 font-black transition"
                                            :class="walletEnough ? 'border-emerald-300/30 bg-emerald-400/15 text-emerald-100 hover:-translate-y-1 hover:bg-emerald-400/20' : 'cursor-not-allowed border-white/10 bg-white/5 text-slate-500'"
                                            :disabled="!walletEnough"
                                        >
                                            <i data-lucide="wallet-cards" class="h-5 w-5"></i>
                                            <span x-text="walletEnough ? 'Thanh toán ví và mở khóa' : (hasSelectedLesson ? 'Ví không đủ' : 'Chọn bài trước')"></span>
                                        </button>
                                    @else
                                        <div class="flex items-center justify-center rounded-2xl border border-dashed border-white/10 bg-white/[.03] px-5 py-4 text-sm font-semibold text-slate-500">
                                            Thanh to&#225;n v&#237; t&#7841;m t&#7855;t
                                        </div>
                                    @endif
                                </div>
                            </form>
                        </div>
                    @endif

                    @if($hasBankQr && $qrImageUrl)
                        <div class="mt-6 rounded-[28px] border border-violet-300/15 bg-black/20 p-4">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-white">M&#227; QR thanh to&#225;n</p>
                                    <p class="mt-1 text-xs text-slate-400">&#7842;nh QR n&#224;y do admin c&#7845;u h&#236;nh ri&#234;ng cho g&#243;i {{ $plan->name }}.</p>
                                </div>
                                <i data-lucide="scan-line" class="h-5 w-5 text-violet-200"></i>
                            </div>
                            <img src="{{ $qrImageUrl }}" alt="QR thanh to&#225;n {{ $plan->name }}" class="mx-auto aspect-square w-full max-w-[260px] rounded-[24px] border border-white/10 bg-white object-cover p-2 shadow-glow">
                            @if($qrDownloadUrl)
                                <a
                                    href="{{ $qrDownloadUrl }}"
                                    download
                                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-violet-300/20 bg-violet-400/10 px-4 py-3 text-sm font-bold text-violet-100 transition hover:-translate-y-0.5 hover:bg-violet-400/15 hover:shadow-glow"
                                >
                                    <i data-lucide="download" class="h-4 w-4"></i>
                                    T&#7843;i m&#227; QR v&#7873; m&#225;y
                                </a>
                            @endif
                        </div>
                    @endif

                    @if(! $isMonthly)
                        <div class="mt-7 grid gap-3 sm:grid-cols-2">
                            @if($hasBankQr)
                                <form method="post" action="{{ route('billing.orders.store') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <input type="hidden" name="payment_method" value="bank_qr">
                                    <button class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1">
                                        <i data-lucide="qr-code" class="h-5 w-5"></i> T&#7841;o QR
                                    </button>
                                </form>
                            @else
                                <div class="flex items-center justify-center rounded-2xl border border-dashed border-white/10 bg-white/[.03] px-5 py-4 text-sm font-semibold text-slate-500">
                                    QR t&#7841;m t&#7855;t
                                </div>
                            @endif

                            @if($hasWallet)
                                <form method="post" action="{{ route('billing.orders.store') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <input type="hidden" name="payment_method" value="wallet">
                                    <button
                                        class="flex w-full items-center justify-center gap-2 rounded-2xl border px-5 py-4 font-black transition {{ $canPayWithWallet ? 'border-emerald-300/30 bg-emerald-400/15 text-emerald-100 hover:-translate-y-1 hover:bg-emerald-400/20' : 'cursor-not-allowed border-white/10 bg-white/5 text-slate-500' }}"
                                        {{ $canPayWithWallet ? '' : 'disabled' }}
                                    >
                                        <i data-lucide="wallet-cards" class="h-5 w-5"></i>
                                        {!! $canPayWithWallet ? 'Thanh to&#225;n v&#237;' : 'V&#237; kh&#244;ng &#273;&#7911;' !!}
                                    </button>
                                </form>
                            @else
                                <div class="flex items-center justify-center rounded-2xl border border-dashed border-white/10 bg-white/[.03] px-5 py-4 text-sm font-semibold text-slate-500">
                                    Thanh to&#225;n v&#237; t&#7841;m t&#7855;t
                                </div>
                            @endif
                        </div>
                    @endif

                    @if(! $hasBankQr && ! $hasWallet)
                        <p class="mt-4 text-sm font-semibold text-amber-200">G&#243;i n&#224;y &#273;ang t&#7841;m t&#7855;t c&#7843; hai ph&#432;&#417;ng th&#7913;c thanh to&#225;n.</p>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-5 flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">Invoice log</p>
                <h2 class="mt-2 text-2xl font-black">L&#7883;ch s&#7917; h&#243;a &#273;&#417;n thanh to&#225;n g&#7847;n &#273;&#226;y</h2>
            </div>
            <i data-lucide="receipt-text" class="h-6 w-6 text-violet-200"></i>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                <tr>
                    <th class="py-3">M&#227;</th>
                    <th>G&#243;i</th>
                    <th>B&#224;i h&#7885;c</th>
                    <th>Ph&#432;&#417;ng th&#7913;c</th>
                    <th>S&#7889; ti&#7873;n</th>
                    <th>Tr&#7841;ng th&#225;i</th>
                    <th>Ng&#224;y t&#7841;o</th>
                    <th></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                @forelse($orders as $order)
                    @php
                        $method = data_get($order->metadata, 'payment_method', 'bank_qr');
                        $methodLabel = $method === 'wallet' ? 'V&#237; s&#7889; d&#432;' : 'QR ng&#226;n h&#224;ng';
                        $statusLabel = $order->status === 'paid' ? '&#272;&#227; thanh to&#225;n' : '&#272;ang ch&#7901;';
                        $selectedLesson = data_get($order->metadata, 'selected_lesson_title');
                    @endphp
                    <tr class="text-slate-300 transition hover:bg-white/[.04]">
                        <td class="py-4 font-mono text-violet-100"><a href="{{ route('billing.orders.show', $order) }}">{{ $order->code }}</a></td>
                        <td>{{ $order->plan?->name }}</td>
                        <td>{{ $selectedLesson ?: '-' }}</td>
                        <td>{{ $methodLabel }}</td>
                        <td>{{ number_format($order->amount_vnd, 0, ',', '.') }} &#273;</td>
                        <td>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $order->status === 'paid' ? 'bg-emerald-400/10 text-emerald-100' : 'bg-amber-300/10 text-amber-100' }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td><a class="text-violet-200 hover:text-white" href="{{ route('billing.orders.show', $order) }}">Chi ti&#7871;t</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400">Ch&#432;a c&#243; h&#243;a &#273;&#417;n thanh to&#225;n.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
