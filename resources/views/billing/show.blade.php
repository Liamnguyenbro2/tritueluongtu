@extends('layouts.app')

@section('content')
@php
    $method = data_get($order->metadata, 'payment_method', 'bank_qr');
    $selectedLesson = data_get($order->metadata, 'selected_lesson_title');
    $isWalletTopup = $order->order_type === \App\Models\PaymentOrder::TYPE_WALLET_TOPUP;
    $qrImageUrl = $method === 'bank_qr' ? $order->vietQrImageUrl() : null;
    $bank = config('quantum.bank_qr');
    $statusLabel = match ($order->status) {
        'paid' => 'Đã thanh toán',
        'expired' => 'Đã hết hạn',
        'cancelled' => 'Đã hủy',
        default => 'Đang chờ thanh toán',
    };
    $statusClass = match ($order->status) {
        'paid' => 'bg-emerald-400/10 text-emerald-100',
        'expired', 'cancelled' => 'bg-rose-400/10 text-rose-100',
        default => 'bg-amber-300/10 text-amber-100',
    };
@endphp

<section class="mx-auto max-w-5xl">
    <div class="glass rounded-[32px] p-6 sm:p-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">SePay checkout</p>
                <h1 class="mt-3 text-3xl font-black sm:text-5xl">{{ $isWalletTopup ? 'Nạp ví bằng QR' : 'Thanh toán đơn hàng' }}</h1>
                <p class="mt-3 text-sm text-slate-400">Hệ thống tự động xác nhận sau khi SePay nhận được giao dịch.</p>
            </div>
            <div id="payment-status" class="rounded-2xl px-4 py-2 text-sm font-bold {{ $statusClass }}">
                {{ $statusLabel }}
            </div>
        </div>

        @if($method === 'bank_qr' && $order->status === 'pending')
            <div class="mt-8 grid gap-6 lg:grid-cols-[.9fr_1.1fr]">
                <div class="rounded-[28px] border border-white/10 bg-black/20 p-5 sm:p-6">
                    <h2 class="text-xl font-black">Thông tin chuyển khoản</h2>
                    <dl class="mt-5 space-y-4 text-sm">
                        @foreach([
                            'Ngân hàng nhận' => $bank['bank_code'] ?? '-',
                            'Số tài khoản' => $bank['account_no'] ?? '-',
                            'Tên chủ tài khoản' => $bank['account_name'] ?? '-',
                            'Số tiền' => number_format($order->amount_vnd, 0, ',', '.').' đ',
                            'Nội dung chuyển khoản' => $order->code,
                        ] as $label => $value)
                            <div class="rounded-2xl border border-white/10 bg-white/[.03] p-4">
                                <dt class="text-slate-400">{{ $label }}</dt>
                                <dd class="mt-2 flex items-center justify-between gap-3 font-bold text-white">
                                    <span class="break-all">{{ $value }}</span>
                                    <button type="button" data-copy-value="{{ $value }}" class="shrink-0 rounded-xl border border-violet-300/20 px-3 py-2 text-xs text-violet-100 hover:bg-violet-400/10">Sao chép</button>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                    <p class="mt-4 text-xs leading-5 text-amber-100/80">
                        Vui lòng giữ nguyên số tiền và nội dung chuyển khoản. Sai một trong hai thông tin, hệ thống sẽ không tự động kích hoạt.
                    </p>
                </div>

                <div class="rounded-[28px] border border-violet-300/15 bg-violet-400/[.06] p-5 text-center sm:p-6">
                    <h2 class="text-xl font-black">Quét mã VietQR</h2>
                    @if($qrImageUrl)
                        <img src="{{ $qrImageUrl }}" alt="VietQR {{ $order->code }}" class="mx-auto mt-5 aspect-square w-full max-w-[340px] rounded-[28px] bg-white object-contain p-3 shadow-glow">
                    @else
                        <div class="mx-auto mt-5 grid aspect-square w-full max-w-[340px] place-items-center rounded-[28px] border border-dashed border-rose-300/30 bg-black/20 p-6 text-sm text-rose-100">
                            Chưa cấu hình BANK_QR_BANK_CODE hoặc BANK_QR_ACCOUNT_NO trên hosting.
                        </div>
                    @endif
                    <p class="mt-4 font-mono text-lg font-black text-violet-100">{{ $order->code }}</p>
                    @if($order->expires_at)
                        <p class="mt-2 text-sm text-slate-400">QR hết hạn lúc {{ $order->expires_at->format('H:i d/m/Y') }}</p>
                    @endif
                </div>
            </div>
        @else
            <div class="mt-8 rounded-[28px] border border-white/10 bg-black/20 p-6">
                <p class="text-sm text-slate-400">Mã đơn hàng</p>
                <p class="mt-2 font-mono text-3xl font-black text-violet-100">{{ $order->code }}</p>
                <p class="mt-5 text-sm text-slate-400">Nội dung</p>
                <p class="mt-2 text-xl font-bold">{{ $order->displayName() }}</p>
                <p class="mt-5 text-sm text-slate-400">Số tiền</p>
                <p class="mt-2 text-3xl font-black">{{ number_format($order->amount_vnd, 0, ',', '.') }} đ</p>
            </div>
        @endif

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('billing') }}" class="rounded-2xl border border-white/10 bg-white/[.05] px-5 py-3 font-bold text-slate-200 hover:bg-white/10">Quay lại nâng cấp</a>
            @if($order->status === 'paid')
                <a href="{{ route('dashboard') }}" class="rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-3 font-black text-white shadow-glow">Sử dụng ngay</a>
            @endif
        </div>
    </div>
</section>
<script>
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-copy-value]');

        if (!button) return;

        await navigator.clipboard.writeText(button.dataset.copyValue || '');
        const original = button.textContent;
        button.textContent = 'Đã sao chép';
        window.setTimeout(() => button.textContent = original, 1200);
    });

    @if($order->status === 'pending')
        const paymentStatusUrl = @json(route('billing.orders.status', $order));
        const currentPaymentStatus = @json($order->status);
        const paymentStatusTimer = window.setInterval(async () => {
            try {
                const response = await fetch(paymentStatusUrl, { headers: { Accept: 'application/json' } });
                if (!response.ok) return;
                const data = await response.json();
                if (data.status !== currentPaymentStatus) {
                    window.clearInterval(paymentStatusTimer);
                    window.location.reload();
                }
            } catch (error) {
                // Keep polling while the customer remains on the checkout screen.
            }
        }, 5000);
    @endif
</script>
@endsection
