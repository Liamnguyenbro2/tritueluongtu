@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-emerald-200/70">Deposits</p>
                <h1 class="mt-2 text-3xl font-black">{!! html_entity_decode('Qu&#7843;n l&#253; n&#7841;p ti&#7873;n') !!}</h1>
            </div>
            <form method="get" class="flex flex-wrap gap-3">
                <input
                    class="premium-input w-52"
                    type="text"
                    name="user"
                    value="{{ $filters['user'] ?? '' }}"
                    placeholder="{!! html_entity_decode('Theo email ho&#7863;c ID t&#224;i kho&#7843;n') !!}"
                >
                <select class="premium-input w-44" name="status">
                    <option value="">{!! html_entity_decode('T&#7845;t c&#7843; tr&#7841;ng th&#225;i') !!}</option>
                    @foreach([
                        'pending' => html_entity_decode('&#272;ang x&#7917; l&#253;'),
                        'paid' => html_entity_decode('Th&#224;nh c&#244;ng'),
                        'failed' => html_entity_decode('Th&#7845;t b&#7841;i'),
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input class="premium-input w-40" type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" min="{{ $dateMin }}" max="{{ $dateMax }}">
                <input class="premium-input w-40" type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" min="{{ $dateMin }}" max="{{ $dateMax }}">
                <button class="rounded-2xl bg-gradient-to-r from-emerald-500 to-cyan-500 px-5 py-3 font-black text-white shadow-glow">{!! html_entity_decode('L&#7885;c') !!}</button>
                <a href="{{ $exportUrl }}" class="rounded-2xl border border-emerald-300/20 bg-emerald-400/10 px-5 py-3 font-black text-emerald-100 transition hover:-translate-y-0.5 hover:bg-emerald-400/15">{!! html_entity_decode('Xu&#7845;t Excel') !!}</a>
            </form>
        </div>
        @if($errors->any())
            <div class="mt-4 rounded-2xl border border-rose-300/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1800px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr>
                        <th class="py-3">STT</th>
                        <th>{!! html_entity_decode('M&#227; giao d&#7883;ch') !!}</th>
                        <th>{!! html_entity_decode('M&#227; &#273;&#7889;i so&#225;t') !!}</th>
                        <th>{!! html_entity_decode('ID T&#224;i kho&#7843;n') !!}</th>
                        <th>{!! html_entity_decode('S&#7889; CCCD') !!}</th>
                        <th>{!! html_entity_decode('H&#7885; t&#234;n CCCD') !!}</th>
                        <th>{!! html_entity_decode('&#272;&#7883;a ch&#7881; CCCD') !!}</th>
                        <th>{!! html_entity_decode('G&#243;i mua') !!}</th>
                        <th>{!! html_entity_decode('S&#7889; ti&#7873;n') !!}</th>
                        <th>{!! html_entity_decode('Ng&#226;n h&#224;ng / k&#234;nh') !!}</th>
                        <th>{!! html_entity_decode('Th&#7901;i gian') !!}</th>
                        <th>{!! html_entity_decode('Tr&#7841;ng th&#225;i') !!}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($orders as $order)
                        @php
                            $method = $order->metadata['payment_method'] ?? 'bank_qr';
                            $channel = match ($method) {
                                'wallet' => html_entity_decode('V&#237; s&#7889; d&#432;'),
                                '9pay' => '9Pay',
                                'vnpay' => 'VNPay',
                                default => 'QR Banking',
                            };
                            $bank = $order->metadata['bank_code'] ?? ($order->metadata['bank_name'] ?? html_entity_decode('Ch&#432;a c&#7853;p nh&#7853;t'));
                            $statusMap = [
                                'paid' => [html_entity_decode('Th&#224;nh c&#244;ng'), 'bg-emerald-400/10 text-emerald-100'],
                                'failed' => [html_entity_decode('Th&#7845;t b&#7841;i'), 'bg-rose-400/10 text-rose-100'],
                                'pending' => [html_entity_decode('&#272;ang x&#7917; l&#253;'), 'bg-amber-300/10 text-amber-100'],
                            ];
                            [$statusLabel, $statusClass] = $statusMap[$order->status] ?? [html_entity_decode('Kh&#225;c'), 'bg-white/10 text-white'];
                            $kyc = $order->user?->kycVerification;
                            $missing = html_entity_decode('Ch&#432;a c&#7853;p nh&#7853;t');
                        @endphp
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4 font-semibold text-white">{{ ($orders->firstItem() ?? 1) + $loop->index }}</td>
                            <td class="font-semibold text-white">{{ $order->code }}</td>
                            <td>{{ $order->provider_transaction_id ?: '-' }}</td>
                            <td>{{ $order->user?->username ?? $missing }}</td>
                            <td>{{ $kyc?->citizen_id ?? $missing }}</td>
                            <td>{{ $kyc?->full_name ?? $missing }}</td>
                            <td class="max-w-[280px] whitespace-normal">{{ $kyc?->address ?? $missing }}</td>
                            <td>{{ $order->plan?->name ?? $missing }}</td>
                            <td class="font-semibold text-emerald-100">{{ number_format((int) $order->amount_vnd, 0, ',', '.') }}{!! html_entity_decode('&#273;') !!}</td>
                            <td>
                                <div class="font-medium text-white">{{ $channel }}</div>
                                <div class="text-xs text-slate-500">{{ $bank }}</div>
                            </td>
                            <td>{{ optional($order->paid_at ?? $order->created_at)?->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-8 text-center text-slate-400">{!! html_entity_decode('Ch&#432;a c&#243; giao d&#7883;ch n&#7841;p ti&#7873;n.') !!}</td>
                        </tr>
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
