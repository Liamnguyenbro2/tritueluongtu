<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $transaction->reference_id }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #111; }
        .card { border: 1px solid #ddd; border-radius: 16px; padding: 24px; }
        .row { margin-bottom: 14px; }
        .label { color: #666; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; }
        .value { font-size: 16px; font-weight: 700; margin-top: 4px; }
    </style>
</head>
<body onload="window.print()">
    <h1>Hóa đơn giao dịch</h1>
    <div class="card">
        <div class="row"><div class="label">Mã giao dịch</div><div class="value">{{ $transaction->reference_id ?: '—' }}</div></div>
        <div class="row"><div class="label">Khách hàng</div><div class="value">{{ $transaction->user?->name }} - {{ $transaction->user?->email }}</div></div>
        <div class="row"><div class="label">Loại</div><div class="value">{{ $transaction->typeLabel() }}</div></div>
        <div class="row"><div class="label">Số tiền</div><div class="value">{{ number_format($transaction->amount, 0, ',', '.') }}đ</div></div>
        <div class="row"><div class="label">Nội dung</div><div class="value">{{ $transaction->description }}</div></div>
        <div class="row"><div class="label">Ghi chú</div><div class="value">{{ $transaction->notes ?: '—' }}</div></div>
        <div class="row"><div class="label">Thời gian</div><div class="value">{{ $transaction->created_at->format('d/m/Y H:i:s') }}</div></div>
        <div class="row"><div class="label">Trạng thái</div><div class="value">{{ $transaction->statusLabel() }}</div></div>
    </div>
</body>
</html>
