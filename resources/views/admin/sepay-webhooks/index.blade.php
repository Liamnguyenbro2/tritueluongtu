@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6 sm:p-8">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-cyan-200/70">Payment Webhooks</p>
                <h1 class="mt-3 text-4xl font-black sm:text-5xl">Nhật ký Webhook SePay</h1>
                <p class="mt-3 max-w-3xl text-slate-400">Lưu toàn bộ dữ liệu webhook SePay để debug, đối soát và chuẩn bị cho bước xử lý thanh toán tự động tiếp theo.</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 px-5 py-4">
                <p class="text-sm text-slate-400">Tổng webhook</p>
                <p class="mt-2 text-3xl font-black">{{ $logs->total() }}</p>
            </div>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        @unless($tableReady)
            <div class="mb-5 rounded-[24px] border border-amber-300/20 bg-amber-400/10 px-5 py-4 text-sm text-amber-100">
                Bảng SePay chưa được tạo trên môi trường này. Hãy chạy <span class="font-mono">php artisan migrate --force</span> trước khi dùng webhook SePay.
            </div>
        @endunless

        <div class="space-y-4">
            @forelse($logs as $log)
                @php
                    $statusClass = match ($log->status) {
                        'processed' => 'bg-emerald-400/10 text-emerald-100',
                        'failed' => 'bg-rose-400/10 text-rose-100',
                        'queued' => 'bg-amber-300/10 text-amber-100',
                        default => 'bg-white/10 text-slate-200',
                    };
                @endphp
                <article class="rounded-[28px] border border-white/10 bg-black/20 p-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-2">
                            <p class="text-sm text-slate-400">Thời gian nhận</p>
                            <p class="text-lg font-bold text-white">{{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                            <p class="text-sm text-slate-400">IP: <span class="font-medium text-slate-200">{{ $log->ip_address ?: '-' }}</span></p>
                            <p class="text-sm text-slate-400">Webhook UUID: <span class="font-mono text-slate-200">{{ $log->webhook_uuid }}</span></p>
                        </div>
                        <span class="self-start rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">
                            {{ strtoupper($log->status) }}
                        </span>
                    </div>

                    <div class="mt-5 grid gap-4 xl:grid-cols-2">
                        <div class="rounded-[24px] border border-white/10 bg-[#070815]/80 p-4">
                            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-slate-400">Payload JSON</p>
                            <pre class="max-h-96 overflow-auto whitespace-pre-wrap break-words text-xs leading-6 text-emerald-100">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                        <div class="rounded-[24px] border border-white/10 bg-[#070815]/80 p-4">
                            <p class="mb-3 text-xs font-semibold uppercase tracking-[.18em] text-slate-400">Header</p>
                            <pre class="max-h-96 overflow-auto whitespace-pre-wrap break-words text-xs leading-6 text-cyan-100">{{ json_encode($log->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-[28px] border border-white/10 bg-black/20 px-6 py-10 text-center text-slate-400">
                    Chưa có webhook SePay nào được ghi nhận.
                </div>
            @endforelse
        </div>

        @if($logs->hasPages())
            <div class="mt-5 rounded-[24px] border border-white/10 bg-black/20 p-3">
                {{ $logs->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
