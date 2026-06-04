@extends('layouts.app')

@section('content')
@php
    $method = data_get($order->metadata, 'payment_method', 'bank_qr');
    $methodLabel = $method === 'wallet' ? 'Ví số dư' : 'QR ngân hàng';
    $statusLabel = $order->status === 'paid' ? 'Đã thanh toán' : 'Đang chờ';
    $qrImageUrl = $method === 'bank_qr' ? $order->plan?->bankQrImageUrl() : null;
@endphp

<section class="mx-auto max-w-3xl">
    <div class="glass rounded-[32px] p-6 sm:p-8">
        <div class="flex items-start justify-between gap-6">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">Payment order</p>
                <h1 class="mt-3 text-4xl font-black">{{ $order->code }}</h1>
            </div>
            <div class="rounded-2xl px-4 py-2 text-sm font-bold {{ $order->status === 'paid' ? 'bg-emerald-400/10 text-emerald-100' : 'bg-amber-300/10 text-amber-100' }}">{{ $statusLabel }}</div>
        </div>

        <div class="mt-8 grid gap-5 sm:grid-cols-2">
            <div class="rounded-[24px] border border-white/10 bg-black/20 p-5">
                <p class="text-sm text-slate-400">Số tiền</p>
                <p class="mt-2 text-3xl font-black">{{ number_format($order->amount_vnd, 0, ',', '.') }} đ</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/20 p-5">
                <p class="text-sm text-slate-400">Phương thức</p>
                <p class="mt-2 text-2xl font-black">{{ $methodLabel }}</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/20 p-5">
                <p class="text-sm text-slate-400">Gói</p>
                <p class="mt-2 text-xl font-bold">{{ $order->plan?->name }}</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/20 p-5">
                <p class="text-sm text-slate-400">Thanh toán lúc</p>
                <p class="mt-2 text-xl font-bold">{{ $order->paid_at?->format('d/m/Y H:i') ?? 'Chưa thanh toán' }}</p>
            </div>
        </div>

        @if($method === 'bank_qr')
            <div class="mt-6 grid gap-5 lg:grid-cols-[.9fr_1.1fr]">
                <div class="rounded-[24px] border border-white/10 bg-black/20 p-5">
                    <p class="text-sm text-slate-400">Mã đơn thanh toán</p>
                    <p class="mt-2 text-3xl font-black text-violet-100">{{ $order->code }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-300">Khi khách hàng chuyển khoản, hệ thống vẫn đối soát theo mã đơn và số tiền. Ảnh QR hiển thị ở đây là ảnh admin đã cấu hình cho gói hiện tại.</p>
                </div>

                <div class="rounded-[24px] border border-white/10 bg-black/20 p-5">
                    <p class="text-sm text-slate-400">Ảnh mã QR</p>
                    @if($qrImageUrl)
                        <img src="{{ $qrImageUrl }}" alt="QR thanh toán {{ $order->plan?->name }}" class="mx-auto mt-4 aspect-square w-full max-w-[280px] rounded-[24px] border border-white/10 bg-white object-cover p-2 shadow-glow">
                    @else
                        <div class="mt-4 grid min-h-[280px] place-items-center rounded-[24px] border border-dashed border-white/10 bg-white/[.03] text-center text-sm text-slate-500">
                            Admin chưa tải ảnh QR cho gói này.
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
