@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_10%,rgba(248,200,78,.2),transparent_30%),radial-gradient(circle_at_20%_80%,rgba(139,92,246,.36),transparent_34%)]"></div>
        <div class="relative flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-amber-200/80">Upgrade Studio</p>
                <h1 class="mt-3 text-4xl font-black sm:text-6xl">Nâng cấp gói</h1>
                <p class="mt-4 max-w-2xl text-slate-300">Thanh toán bằng QR ngân hàng hoặc dùng ví số dư khi tài khoản có đủ tiền. Thời hạn gói mới sẽ cộng dồn vào thời gian còn lại hiện tại.</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 p-5">
                <p class="text-sm text-slate-400">Số dư ví</p>
                <p class="mt-1 text-2xl font-black">{{ number_format($wallet->balance_vnd, 0, ',', '.') }} đ</p>
            </div>
        </div>
    </section>

    <section class="grid gap-5 lg:grid-cols-2">
        @foreach($plans as $plan)
            @php $canPayWithWallet = $wallet->balance_vnd >= $plan->price_vnd; @endphp
            <article class="group relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-2xl shadow-black/30 backdrop-blur-2xl transition duration-300 hover:-translate-y-2 hover:border-amber-200/40 hover:shadow-gold">
                <div class="absolute -right-16 -top-16 h-44 w-44 rounded-full bg-violet-500/30 blur-3xl transition group-hover:bg-amber-300/25"></div>
                <div class="relative">
                    <div class="mb-5 flex items-center justify-between">
                        <div class="grid h-14 w-14 place-items-center rounded-3xl bg-gradient-to-br from-amber-300 to-violet-500 shadow-gold">
                            <i data-lucide="{{ $plan->code === 'yearly' ? 'crown' : 'calendar-days' }}" class="h-7 w-7 text-white"></i>
                        </div>
                        @if($plan->code === 'yearly')
                            <span class="rounded-full bg-amber-300 px-3 py-1 text-xs font-black text-night">Best value</span>
                        @endif
                    </div>

                    <h2 class="text-3xl font-black">{{ $plan->name }}</h2>
                    <p class="mt-4 bg-gradient-to-r from-white to-violet-200 bg-clip-text text-5xl font-black text-transparent">{{ number_format($plan->price_vnd, 0, ',', '.') }} đ</p>
                    <p class="mt-2 text-slate-400">{{ $plan->duration_days }} ngày sử dụng</p>

                    <ul class="mt-6 space-y-3 text-sm text-slate-300">
                        <li class="flex items-center gap-3"><i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-300"></i> Mở quyền kích hoạt các khóa trả phí</li>
                        <li class="flex items-center gap-3"><i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-300"></i> Active từng khóa trong 7 ngày khi cần học</li>
                        <li class="flex items-center gap-3"><i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-300"></i> Ghi nhận đầy đủ trong lịch sử hóa đơn</li>
                    </ul>

                    <div class="mt-7 grid gap-3 sm:grid-cols-2">
                        <form method="post" action="{{ route('billing.orders.store') }}">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <input type="hidden" name="payment_method" value="bank_qr">
                            <button class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1">
                                <i data-lucide="qr-code" class="h-5 w-5"></i> Tạo QR
                            </button>
                        </form>

                        <form method="post" action="{{ route('billing.orders.store') }}">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <input type="hidden" name="payment_method" value="wallet">
                            <button
                                class="flex w-full items-center justify-center gap-2 rounded-2xl border px-5 py-4 font-black transition {{ $canPayWithWallet ? 'border-emerald-300/30 bg-emerald-400/15 text-emerald-100 hover:-translate-y-1 hover:bg-emerald-400/20' : 'cursor-not-allowed border-white/10 bg-white/5 text-slate-500' }}"
                                @disabled(! $canPayWithWallet)
                            >
                                <i data-lucide="wallet-cards" class="h-5 w-5"></i>
                                {{ $canPayWithWallet ? 'Thanh toán ví' : 'Ví không đủ' }}
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-5 flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">Invoice log</p>
                <h2 class="mt-2 text-2xl font-black">Lịch sử hóa đơn thanh toán gần đây</h2>
            </div>
            <i data-lucide="receipt-text" class="h-6 w-6 text-violet-200"></i>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                <tr>
                    <th class="py-3">Mã</th>
                    <th>Gói</th>
                    <th>Phương thức</th>
                    <th>Số tiền</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                @forelse($orders as $order)
                    @php
                        $method = data_get($order->metadata, 'payment_method', 'bank_qr');
                        $methodLabel = $method === 'wallet' ? 'Ví số dư' : 'QR ngân hàng';
                        $statusLabel = $order->status === 'paid' ? 'Đã thanh toán' : 'Đang chờ';
                    @endphp
                    <tr class="text-slate-300 transition hover:bg-white/[.04]">
                        <td class="py-4 font-mono text-violet-100"><a href="{{ route('billing.orders.show', $order) }}">{{ $order->code }}</a></td>
                        <td>{{ $order->plan?->name }}</td>
                        <td>{{ $methodLabel }}</td>
                        <td>{{ number_format($order->amount_vnd, 0, ',', '.') }} đ</td>
                        <td>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $order->status === 'paid' ? 'bg-emerald-400/10 text-emerald-100' : 'bg-amber-300/10 text-amber-100' }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td><a class="text-violet-200 hover:text-white" href="{{ route('billing.orders.show', $order) }}">Chi tiết</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">Chưa có hóa đơn thanh toán.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
