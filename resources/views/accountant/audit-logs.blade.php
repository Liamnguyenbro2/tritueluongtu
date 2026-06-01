@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6">
        <p class="text-sm font-semibold uppercase tracking-[.22em] text-violet-200/70">Audit Log</p>
        <h1 class="mt-2 text-3xl font-black">Nhật ký kế toán</h1>
        <p class="mt-3 text-slate-300">Tất cả hành động duyệt, từ chối, điều chỉnh số dư và thao tác đối soát đều được lưu tại đây. Log không cho phép sửa hoặc xóa.</p>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr>
                        <th class="py-3">Thời gian</th>
                        <th>Người thao tác</th>
                        <th>Hành động</th>
                        <th>Đối tượng</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($logs as $log)
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>
                                <div class="font-semibold text-white">{{ $log->actor?->name }}</div>
                                <div class="text-xs text-slate-500">{{ $log->actor?->email }}</div>
                            </td>
                            <td class="font-medium text-white">{{ $log->description }}</td>
                            <td>{{ $log->target_type ? class_basename($log->target_type).' #'.$log->target_id : '—' }}</td>
                            <td>{{ $log->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-slate-400">Chưa có audit log.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="mt-5 rounded-[24px] border border-white/10 bg-black/20 p-3">
                {{ $logs->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
