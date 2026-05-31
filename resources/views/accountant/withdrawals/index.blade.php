@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-rose-200/70">Withdrawals</p>
                <h1 class="mt-2 text-3xl font-black">Quản lý yêu cầu rút tiền</h1>
            </div>
            <form method="get" class="flex flex-wrap gap-3">
                <input class="premium-input w-56" type="text" name="user" value="{{ $filters['user'] ?? '' }}" placeholder="Theo user">
                <select class="premium-input w-48" name="status">
                    <option value="">Tất cả trạng thái</option>
                    @foreach(['pending' => 'Chờ xử lý', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối', 'transferred' => 'Đã chuyển khoản'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="rounded-2xl bg-gradient-to-r from-rose-500 to-violet-500 px-5 py-3 font-black text-white shadow-glow">Lọc</button>
            </form>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1180px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr>
                        <th class="py-3">Mã</th>
                        <th>User</th>
                        <th>Số tiền</th>
                        <th>Ngân hàng</th>
                        <th>STK</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($withdrawals as $withdrawal)
                        @php
                            $statusMap = [
                                'pending' => ['Chờ xử lý', 'bg-amber-300/10 text-amber-100'],
                                'approved' => ['Đã duyệt', 'bg-emerald-400/10 text-emerald-100'],
                                'rejected' => ['Từ chối', 'bg-rose-400/10 text-rose-100'],
                                'transferred' => ['Đã chuyển khoản', 'bg-sky-400/10 text-sky-100'],
                            ];
                            [$statusLabel, $statusClass] = $statusMap[$withdrawal->status] ?? ['Khác', 'bg-white/10 text-white'];
                        @endphp
                        <tr class="align-top text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4 font-semibold text-white">WD-{{ $withdrawal->id }}</td>
                            <td>{{ $withdrawal->user?->name }}<div class="text-xs text-slate-500">{{ $withdrawal->user?->email }}</div></td>
                            <td class="font-semibold text-white">{{ number_format($withdrawal->amount_vnd, 0, ',', '.') }}đ</td>
                            <td>{{ $withdrawal->bankAccount?->bank_name }}</td>
                            <td>{{ $withdrawal->bankAccount?->account_number }}</td>
                            <td>{{ $withdrawal->created_at->format('d/m/Y H:i:s') }}</td>
                            <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    @if($withdrawal->status === 'pending')
                                        <form method="post" action="{{ route('accountant.withdrawals.approve', $withdrawal) }}">@csrf<button class="rounded-xl border border-emerald-300/20 bg-emerald-400/10 px-3 py-2 text-xs font-bold text-emerald-100">Duyệt</button></form>
                                        <form method="post" action="{{ route('accountant.withdrawals.reject', $withdrawal) }}" class="flex items-center gap-2">@csrf<input class="premium-input w-44 px-3 py-2 text-xs" type="text" name="note" placeholder="Lý do từ chối" required><button class="rounded-xl border border-rose-300/20 bg-rose-400/10 px-3 py-2 text-xs font-bold text-rose-100">Từ chối</button></form>
                                    @endif
                                    @if($withdrawal->status === 'approved')
                                        <form method="post" action="{{ route('accountant.withdrawals.mark-transferred', $withdrawal) }}">@csrf<button class="rounded-xl border border-sky-300/20 bg-sky-400/10 px-3 py-2 text-xs font-bold text-sky-100">Đã chuyển khoản</button></form>
                                    @endif
                                    @if(in_array($withdrawal->status, ['approved', 'transferred'], true))
                                        <form method="post" action="{{ route('accountant.withdrawals.resend', $withdrawal) }}">@csrf<button class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-bold text-slate-100">Gửi lại lệnh</button></form>
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
                        <tr><td colspan="8" class="py-8 text-center text-slate-400">Chưa có yêu cầu rút tiền.</td></tr>
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
