@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6 sm:p-8">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-cyan-200/70">KYC Management</p>
                <h1 class="mt-3 text-4xl font-black sm:text-5xl">Qu&#7843;n l&#253; KYC</h1>
                <p class="mt-3 max-w-3xl text-slate-400">
                    Danh s&#225;ch KYC to&#224;n h&#7879; th&#7889;ng, ph&#7909;c v&#7909; tra c&#7913;u, nghi&#7879;p v&#7909; v&#224; xu&#7845;t d&#7919; li&#7879;u khi c&#7847;n.
                </p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-[24px] border border-white/10 bg-black/25 px-5 py-4">
                    <p class="text-sm text-slate-400">T&#7893;ng h&#7891; s&#417; KYC</p>
                    <p class="mt-2 text-3xl font-black">{{ $stats['total'] }}</p>
                </div>
                <div class="rounded-[24px] border border-white/10 bg-black/25 px-5 py-4">
                    <p class="text-sm text-slate-400">G&#7917;i h&#244;m nay</p>
                    <p class="mt-2 text-3xl font-black">{{ $stats['submitted_today'] }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-black">Danh s&#225;ch KYC</h2>
                <p class="mt-1 text-sm text-slate-400">T&#236;m theo h&#7885; t&#234;n, s&#7889; CCCD, ID t&#224;i kho&#7843;n ho&#7863;c email.</p>
            </div>
            <div class="flex w-full flex-col gap-3 lg:w-auto lg:min-w-[620px] lg:flex-row">
                <form method="get" action="{{ route('admin.kyc.index') }}" class="flex flex-1 gap-2">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                        <input class="premium-input pl-11" name="q" value="{{ $search }}" placeholder="H&#7885; t&#234;n, s&#7889; CCCD, ID t&#224;i kho&#7843;n ho&#7863;c email">
                    </div>
                    <button class="rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-3 font-bold shadow-glow transition hover:-translate-y-0.5">T&#236;m</button>
                </form>
                <a href="{{ route('admin.kyc.export', ['q' => $search]) }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-300/20 bg-emerald-400/10 px-5 py-3 font-bold text-emerald-100 transition hover:-translate-y-0.5">
                    <i data-lucide="download" class="h-4 w-4"></i>
                    Xu&#7845;t d&#7919; li&#7879;u
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr>
                        <th class="py-3">STT</th>
                        <th>ID t&#224;i kho&#7843;n</th>
                        <th>H&#7885; t&#234;n</th>
                        <th>S&#7889; CCCD</th>
                        <th>&#272;&#7883;a ch&#7881;</th>
                        <th>Ng&#224;y g&#7917;i</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($records as $index => $record)
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4 font-semibold text-white">{{ ($records->firstItem() ?? 1) + $index }}</td>
                            <td class="font-bold text-white">{{ $record->user?->username ?? '#'.$record->user_id }}</td>
                            <td>{{ $record->full_name }}</td>
                            <td>{{ $record->citizen_id }}</td>
                            <td class="max-w-[320px] break-anywhere">{{ $record->address }}</td>
                            <td>{{ $record->submitted_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400">Ch&#432;a c&#243; h&#7891; s&#417; KYC n&#224;o.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="mt-5 rounded-[24px] border border-white/10 bg-black/20 p-3">
                {{ $records->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
