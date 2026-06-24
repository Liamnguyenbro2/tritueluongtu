@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-rose-200/70">Withdrawals</p>
                <h1 class="mt-2 text-3xl font-black">{!! html_entity_decode('Qu&#7843;n l&#253; y&#234;u c&#7847;u r&#250;t ti&#7873;n') !!}</h1>
            </div>
            <form method="get" class="flex flex-wrap gap-3">
                <input
                    class="premium-input w-56"
                    type="text"
                    name="user"
                    value="{{ $filters['user'] ?? '' }}"
                    placeholder="{!! html_entity_decode('Theo email ho&#7863;c ID t&#224;i kho&#7843;n') !!}"
                >
                <select class="premium-input w-48" name="status">
                    <option value="">{!! html_entity_decode('T&#7845;t c&#7843; tr&#7841;ng th&#225;i') !!}</option>
                    @foreach([
                        'pending' => html_entity_decode('Ch&#7901; duy&#7879;t'),
                        'approved' => html_entity_decode('&#272;ang x&#7917; l&#253;'),
                        'rejected' => html_entity_decode('T&#7915; ch&#7889;i'),
                        'transferred' => html_entity_decode('Ho&#224;n th&#224;nh'),
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="rounded-2xl bg-gradient-to-r from-rose-500 to-violet-500 px-5 py-3 font-black text-white shadow-glow">
                    {!! html_entity_decode('L&#7885;c') !!}
                </button>
            </form>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm text-slate-400">{!! html_entity_decode('Xu&#7845;t d&#7919; li&#7879;u y&#234;u c&#7847;u r&#250;t ti&#7873;n theo ng&#224;y trong ph&#7841;m vi 7 ng&#224;y g&#7847;n nh&#7845;t.') !!}</p>
            </div>
            <form method="get" action="{{ route('accountant.withdrawals.export') }}" class="flex flex-wrap items-end gap-3">
                <label class="grid gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400">{!! html_entity_decode('Ng&#224;y xu&#7845;t') !!}</span>
                    <input
                        class="premium-input w-52"
                        type="date"
                        name="export_date"
                        value="{{ $exportDate }}"
                        min="{{ $exportDateMin }}"
                        max="{{ $exportDateMax }}"
                        required
                    >
                </label>
                <button class="rounded-2xl border border-emerald-300/20 bg-emerald-400/10 px-5 py-3 font-black text-emerald-100 transition hover:-translate-y-0.5 hover:bg-emerald-400/15">
                    {!! html_entity_decode('Xu&#7845;t Excel') !!}
                </button>
            </form>
        </div>

        @error('export_date')
            <div class="mb-4 rounded-2xl border border-rose-300/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
                {{ $message }}
            </div>
        @enderror

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1660px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr>
                        <th class="py-3">STT</th>
                        <th>{!! html_entity_decode('ID T&#224;i kho&#7843;n') !!}</th>
                        <th>{!! html_entity_decode('S&#7889; CCCD') !!}</th>
                        <th>{!! html_entity_decode('H&#7885; t&#234;n CCCD') !!}</th>
                        <th>{!! html_entity_decode('Ng&#226;n h&#224;ng') !!}</th>
                        <th>{!! html_entity_decode('STK Ng&#226;n h&#224;ng') !!}</th>
                        <th>{!! html_entity_decode('T&#7893;ng ti&#7873;n r&#250;t') !!}</th>
                        <th>{!! html_entity_decode('Thu&#7871; TNCN') !!}</th>
                        <th>{!! html_entity_decode('Th&#7921;c nh&#7853;n') !!}</th>
                        <th>{!! html_entity_decode('Th&#7901;i gian') !!}</th>
                        <th>{!! html_entity_decode('Tr&#7841;ng th&#225;i') !!}</th>
                        <th>{!! html_entity_decode('H&#224;nh &#273;&#7897;ng') !!}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($withdrawals as $withdrawal)
                        @php
                            $statusMap = [
                                'pending' => [html_entity_decode('Ch&#7901; duy&#7879;t'), 'bg-amber-300/10 text-amber-100'],
                                'approved' => [html_entity_decode('&#272;ang x&#7917; l&#253;'), 'bg-sky-400/10 text-sky-100'],
                                'rejected' => [html_entity_decode('T&#7915; ch&#7889;i'), 'bg-rose-400/10 text-rose-100'],
                                'transferred' => [html_entity_decode('Ho&#224;n th&#224;nh'), 'bg-emerald-400/10 text-emerald-100'],
                            ];
                            [$statusLabel, $statusClass] = $statusMap[$withdrawal->status] ?? ['Khác', 'bg-white/10 text-white'];
                            $kyc = $withdrawal->user?->kycVerification;
                        @endphp
                        <tr class="align-top text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4 font-semibold text-white">{{ $withdrawal->withdrawal_number ?? '-' }}</td>
                            <td class="font-semibold text-white">{{ $withdrawal->user?->username ?? '-' }}</td>
                            <td>{{ $kyc?->citizen_id ?? '-' }}</td>
                            <td>{{ $kyc?->full_name ?? '-' }}</td>
                            <td>{{ $withdrawal->bankAccount?->bank_name ?? '-' }}</td>
                            <td>{{ $withdrawal->bankAccount?->account_number ?? '-' }}</td>
                            <td class="font-semibold text-white">{{ number_format((int) $withdrawal->amount_vnd, 0, ',', '.') }}đ</td>
                            <td class="font-semibold text-amber-100">{{ number_format((int) $withdrawal->pit_amount_vnd, 0, ',', '.') }}đ</td>
                            <td class="font-semibold text-emerald-100">{{ number_format((int) ($withdrawal->net_amount_vnd ?? $withdrawal->amount_vnd), 0, ',', '.') }}đ</td>
                            <td>{{ $withdrawal->created_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    @if($withdrawal->status === 'pending')
                                        <form method="post" action="{{ route('accountant.withdrawals.approve', $withdrawal) }}">
                                            @csrf
                                            <button class="rounded-xl border border-emerald-300/20 bg-emerald-400/10 px-3 py-2 text-xs font-bold text-emerald-100">
                                                {!! html_entity_decode('Duy&#7879;t') !!}
                                            </button>
                                        </form>
                                        <form method="post" action="{{ route('accountant.withdrawals.reject', $withdrawal) }}" class="flex items-center gap-2">
                                            @csrf
                                            <input
                                                class="premium-input w-44 px-3 py-2 text-xs"
                                                type="text"
                                                name="note"
                                                placeholder="{!! html_entity_decode('L&#253; do t&#7915; ch&#7889;i') !!}"
                                                required
                                            >
                                            <button class="rounded-xl border border-rose-300/20 bg-rose-400/10 px-3 py-2 text-xs font-bold text-rose-100">
                                                {!! html_entity_decode('T&#7915; ch&#7889;i') !!}
                                            </button>
                                        </form>
                                    @endif
                                    @if($withdrawal->status === 'approved')
                                        <form method="post" action="{{ route('accountant.withdrawals.mark-transferred', $withdrawal) }}">
                                            @csrf
                                            <button class="rounded-xl border border-sky-300/20 bg-sky-400/10 px-3 py-2 text-xs font-bold text-sky-100">
                                                {!! html_entity_decode('&#272;&#227; chuy&#7875;n kho&#7843;n') !!}
                                            </button>
                                        </form>
                                    @endif
                                    @if(in_array($withdrawal->status, ['approved', 'transferred'], true))
                                        <form method="post" action="{{ route('accountant.withdrawals.resend', $withdrawal) }}">
                                            @csrf
                                            <button class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-bold text-slate-100">
                                                {!! html_entity_decode('G&#7917;i l&#7841;i l&#7879;nh') !!}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                @if(($auditLogByWithdrawal[$withdrawal->id] ?? collect())->isNotEmpty())
                                    <div class="mt-3 space-y-1 rounded-[18px] border border-white/10 bg-black/20 p-3 text-xs text-slate-400">
                                        @foreach(($auditLogByWithdrawal[$withdrawal->id] ?? collect())->take(3) as $log)
                                            <div>{{ $log->created_at->format('d/m H:i') }} - {{ $log->actor?->name }} - {{ $log->description }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-8 text-center text-slate-400">{!! html_entity_decode('Ch&#432;a c&#243; y&#234;u c&#7847;u r&#250;t ti&#7873;n.') !!}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($withdrawals->hasPages())
            <div class="mt-5 rounded-[24px] border border-white/10 bg-black/20 p-3">{{ $withdrawals->onEachSide(1)->links() }}</div>
        @endif
    </section>
</div>
@endsection
