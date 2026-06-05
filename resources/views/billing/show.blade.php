@extends('layouts.app')

@section('content')
@php
    $method = data_get($order->metadata, 'payment_method', 'bank_qr');
    $methodLabel = $method === 'wallet' ? 'V&#237; s&#7889; d&#432;' : 'QR ng&#226;n h&#224;ng';
    $statusLabel = $order->status === 'paid' ? '&#272;&#227; thanh to&#225;n' : '&#272;ang ch&#7901;';
    $qrImageUrl = $method === 'bank_qr' ? $order->plan?->bankQrImageUrl() : null;
    $selectedLesson = data_get($order->metadata, 'selected_lesson_title');
@endphp

<section class="mx-auto max-w-3xl">
    <div class="glass rounded-[32px] p-6 sm:p-8">
        <div class="flex items-start justify-between gap-6">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">Payment order</p>
                <h1 class="mt-3 text-4xl font-black">{{ $order->code }}</h1>
            </div>
            <div class="rounded-2xl px-4 py-2 text-sm font-bold {{ $order->status === 'paid' ? 'bg-emerald-400/10 text-emerald-100' : 'bg-amber-300/10 text-amber-100' }}">
                {{ $statusLabel }}
            </div>
        </div>

        <div class="mt-8 grid gap-5 sm:grid-cols-2">
            <div class="rounded-[24px] border border-white/10 bg-black/20 p-5">
                <p class="text-sm text-slate-400">S&#7889; ti&#7873;n</p>
                <p class="mt-2 text-3xl font-black">{{ number_format($order->amount_vnd, 0, ',', '.') }} &#273;</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/20 p-5">
                <p class="text-sm text-slate-400">Ph&#432;&#417;ng th&#7913;c</p>
                <p class="mt-2 text-2xl font-black">{{ $methodLabel }}</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/20 p-5">
                <p class="text-sm text-slate-400">G&#243;i</p>
                <p class="mt-2 text-xl font-bold">{{ $order->plan?->name }}</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/20 p-5">
                <p class="text-sm text-slate-400">B&#224;i h&#7885;c &#273;&#227; ch&#7885;n</p>
                <p class="mt-2 text-xl font-bold">{!! $selectedLesson ?: 'Kh&#244;ng c&#243;' !!}</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/20 p-5 sm:col-span-2">
                <p class="text-sm text-slate-400">Thanh to&#225;n l&#250;c</p>
                <p class="mt-2 text-xl font-bold">{!! $order->paid_at?->format('d/m/Y H:i') ?? 'Ch&#432;a thanh to&#225;n' !!}</p>
            </div>
        </div>

        @if($method === 'bank_qr')
            <div class="mt-6 grid gap-5 lg:grid-cols-[.9fr_1.1fr]">
                <div class="rounded-[24px] border border-white/10 bg-black/20 p-5">
                    <p class="text-sm text-slate-400">M&#227; &#273;&#417;n thanh to&#225;n</p>
                    <p class="mt-2 text-3xl font-black text-violet-100">{{ $order->code }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-300">
                        @if($selectedLesson)
                            &#272;&#417;n h&#224;ng n&#224;y s&#7869; m&#7903; tr&#7921;c ti&#7871;p b&#224;i <strong>{{ $selectedLesson }}</strong> sau khi h&#7879; th&#7889;ng nh&#7853;n thanh to&#225;n th&#224;nh c&#244;ng.
                        @else
                            &#272;&#417;n h&#224;ng n&#224;y &#273;ang ch&#7901; h&#7879; th&#7889;ng x&#225;c nh&#7853;n thanh to&#225;n.
                        @endif
                    </p>
                </div>

                <div class="rounded-[24px] border border-white/10 bg-black/20 p-5">
                    <p class="text-sm text-slate-400">&#7842;nh m&#227; QR</p>
                    @if($qrImageUrl)
                        <img src="{{ $qrImageUrl }}" alt="QR thanh to&#225;n {{ $order->plan?->name }}" class="mx-auto mt-4 aspect-square w-full max-w-[280px] rounded-[24px] border border-white/10 bg-white object-cover p-2 shadow-glow">
                    @else
                        <div class="mt-4 grid min-h-[280px] place-items-center rounded-[24px] border border-dashed border-white/10 bg-white/[.03] text-center text-sm text-slate-500">
                            Admin ch&#432;a t&#7843;i &#7843;nh QR cho g&#243;i n&#224;y.
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 rounded-[24px] border border-white/10 bg-black/20 p-5">
                <p class="text-sm text-slate-400">QR metadata</p>
                <pre class="mt-3 overflow-x-auto rounded-2xl bg-black/40 p-4 text-xs text-violet-100">{{ json_encode($order->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif
    </div>
</section>
@endsection
