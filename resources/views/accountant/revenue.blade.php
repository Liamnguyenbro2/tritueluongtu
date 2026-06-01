@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="glass rounded-[32px] p-6">
        <p class="text-sm font-semibold uppercase tracking-[.22em] text-fuchsia-200/70">Revenue</p>
        <h1 class="mt-2 text-3xl font-black">Quản lý doanh thu hệ thống</h1>
        <p class="mt-3 text-slate-300">Theo dõi doanh thu đã thanh toán theo từng gói dịch vụ.</p>
    </section>

    <section class="grid gap-5 xl:grid-cols-[320px_1fr]">
        <div class="glass rounded-[32px] p-6">
            <p class="text-xs uppercase tracking-[.18em] text-slate-400">Tổng doanh thu</p>
            <p class="mt-4 text-4xl font-black text-white">{{ number_format($totalRevenue, 0, ',', '.') }}đ</p>
        </div>

        <div class="glass rounded-[32px] p-6">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                        <tr>
                            <th class="py-3">Gói</th>
                            <th>Giá</th>
                            <th>Số đơn paid</th>
                            <th>Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($planRevenue as $plan)
                            <tr class="text-slate-300 transition hover:bg-white/[.04]">
                                <td class="py-4 font-semibold text-white">{{ $plan->name }}</td>
                                <td>{{ number_format($plan->price_vnd, 0, ',', '.') }}đ</td>
                                <td>{{ $plan->order_count }}</td>
                                <td class="font-semibold text-fuchsia-100">{{ number_format((int) $plan->revenue_vnd, 0, ',', '.') }}đ</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400">Chưa có doanh thu theo gói.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection
