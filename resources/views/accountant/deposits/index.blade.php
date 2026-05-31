@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-emerald-200/70">Deposits</p>
                <h1 class="mt-2 text-3xl font-black">Quan ly nap tien</h1>
            </div>
            <form method="get" class="flex flex-wrap gap-3">
                <input class="premium-input w-56" type="text" name="user" value="{{ $filters['user'] ?? '' }}" placeholder="Theo user">
                <select class="premium-input w-48" name="status">
                    <option value="">Tat ca trang thai</option>
                    @foreach(['pending' => 'Dang xu ly', 'paid' => 'Thanh cong', 'failed' => 'That bai'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="rounded-2xl bg-gradient-to-r from-emerald-500 to-cyan-500 px-5 py-3 font-black text-white shadow-glow">Loc</button>
            </form>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr>
                        <th class="py-3">Ma giao dich</th>
                        <th>Ma doi soat</th>
                        <th>User</th>
                        <th>Goi</th>
                        <th>So tien</th>
                        <th>Ngan hang / kenh</th>
                        <th>Thoi gian</th>
                        <th>Trang thai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($orders as $order)
                        @php
                            $method = $order->metadata['payment_method'] ?? 'bank_qr';
                            $channel = match ($method) {
                                'wallet' => 'Vi so du',
                                '9pay' => '9Pay',
                                'vnpay' => 'VNPay',
                                default => 'QR Banking',
                            };
                            $bank = $order->metadata['bank_code'] ?? ($order->metadata['bank_name'] ?? 'Chua cap nhat');
                            $statusMap = [
                                'paid' => ['Thanh cong', 'bg-emerald-400/10 text-emerald-100'],
                                'failed' => ['That bai', 'bg-rose-400/10 text-rose-100'],
                                'pending' => ['Dang xu ly', 'bg-amber-300/10 text-amber-100'],
                            ];
                            [$statusLabel, $statusClass] = $statusMap[$order->status] ?? ['Khac', 'bg-white/10 text-white'];
                        @endphp
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4 font-semibold text-white">{{ $order->code }}</td>
                            <td>{{ $order->provider_transaction_id ?: '—' }}</td>
                            <td>{{ $order->user?->email }}</td>
                            <td>{{ $order->plan?->name }}</td>
                            <td class="font-semibold text-emerald-100">{{ number_format($order->amount_vnd, 0, ',', '.') }}d</td>
                            <td>
                                <div class="font-medium text-white">{{ $channel }}</div>
                                <div class="text-xs text-slate-500">{{ $bank }}</div>
                            </td>
                            <td>{{ optional($order->paid_at ?? $order->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-8 text-center text-slate-400">Chua co giao dich nap tien.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="mt-5 rounded-[24px] border border-white/10 bg-black/20 p-3">
                {{ $orders->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
