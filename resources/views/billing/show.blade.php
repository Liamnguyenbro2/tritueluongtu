@extends('layouts.app')

@section('content')
@php
    $method = data_get($order->metadata, 'payment_method', 'bank_qr');
    $selectedLesson = data_get($order->metadata, 'selected_lesson_title');
    $isWalletTopup = $order->order_type === \App\Models\PaymentOrder::TYPE_WALLET_TOPUP;
    $qrImageUrl = $method === 'bank_qr' ? $order->vietQrImageUrl() : null;
    $localQrImageUrl = $qrImageUrl ? route('billing.orders.qr-image', $order) : null;
    $bank = $paymentSettings;
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
                    @if($localQrImageUrl)
                        <img src="{{ $localQrImageUrl }}" alt="VietQR {{ $order->code }}" class="mx-auto mt-5 aspect-square w-full max-w-[340px] rounded-[28px] bg-white object-contain p-3 shadow-glow" style="-webkit-touch-callout: default">
                        <button
                            type="button"
                            data-save-image-url="{{ $localQrImageUrl }}"
                            data-save-image-download-url="{{ route('billing.orders.qr-image', [$order, 'download' => 1]) }}"
                            data-save-image-name="vietqr-{{ $order->code }}.png"
                            class="mx-auto mt-4 flex w-full max-w-[340px] items-center justify-center gap-2 rounded-2xl border border-violet-200/20 bg-white/[.06] px-4 py-3 text-sm font-bold text-violet-100 transition hover:bg-white/10"
                        >
                            <span aria-hidden="true">&#8681;</span>
                            Lưu mã QR về máy
                        </button>
                        <p class="mx-auto mt-3 max-w-[340px] text-xs leading-5 text-slate-400">
                            Trên iPhone/iPad, chọn <strong class="text-slate-200">Lưu hình ảnh</strong> trong bảng chia sẻ. Nếu bảng chia sẻ không hiện, hãy giữ chạm vào ảnh QR để lưu vào Ảnh.
                        </p>
                    @else
                        <div class="mx-auto mt-5 grid aspect-square w-full max-w-[340px] place-items-center rounded-[28px] border border-dashed border-rose-300/30 bg-black/20 p-6 text-sm text-rose-100">
                            Admin chưa cấu hình đầy đủ tài khoản nhận thanh toán.
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
    async function saveImageToDevice(imageUrl, fileName, downloadUrl) {
        try {
            const response = await fetch(imageUrl, { credentials: 'same-origin' });

            if (!response.ok) {
                throw new Error('Unable to fetch QR image');
            }

            const blob = await response.blob();
            const file = new File([blob], fileName, { type: blob.type || 'image/png' });
            const canShareFile = typeof navigator.share === 'function'
                && typeof navigator.canShare === 'function'
                && navigator.canShare({ files: [file] });

            if (canShareFile) {
                await navigator.share({
                    files: [file],
                    title: fileName,
                    text: 'Lưu mã QR này vào thiết bị của bạn.',
                });

                return;
            }

            const objectUrl = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = objectUrl;
            anchor.download = fileName;
            anchor.rel = 'noopener';
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
            window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
        } catch (error) {
            if (error && error.name === 'AbortError') {
                return;
            }

            const isAppleMobile = /iPad|iPhone|iPod/.test(navigator.userAgent)
                || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

            if (isAppleMobile) {
                window.open(imageUrl, '_blank', 'noopener');
                return;
            }

            window.location.assign(downloadUrl || imageUrl);
        }
    }

    document.addEventListener('click', async (event) => {
        const saveButton = event.target.closest('[data-save-image-url]');

        if (saveButton) {
            event.preventDefault();
            await saveImageToDevice(
                saveButton.dataset.saveImageUrl,
                saveButton.dataset.saveImageName || 'vietqr.png',
                saveButton.dataset.saveImageDownloadUrl
            );
            return;
        }

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
