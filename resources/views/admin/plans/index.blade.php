@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_85%_18%,rgba(16,185,129,.18),transparent_28%),radial-gradient(circle_at_15%_78%,rgba(139,92,246,.28),transparent_30%)]"></div>
        <div class="relative">
            <p class="text-sm font-semibold uppercase tracking-[.24em] text-emerald-200/80">Plan Control</p>
            <h1 class="mt-3 text-4xl font-black sm:text-6xl">Quản lý gói nâng cấp</h1>
            <p class="mt-4 max-w-3xl text-slate-300">Sửa giá gói, nội dung mô tả, danh sách tính năng và bật tắt từng phương thức thanh toán cho mỗi gói.</p>
        </div>
    </section>

    <section class="grid gap-6">
        @foreach($plans as $plan)
            @php $isSelected = optional($selectedPlan)->id === $plan->id; @endphp
            <article id="plan-{{ $plan->id }}" class="glass rounded-[32px] p-6 {{ $isSelected ? 'ring-2 ring-emerald-300/60' : '' }}">
                <div class="mb-6 flex flex-col justify-between gap-4 xl:flex-row xl:items-start">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[.22em] text-emerald-200/70">{{ strtoupper($plan->code) }}</p>
                        <h2 class="mt-2 text-3xl font-black">{{ $plan->name }}</h2>
                        <p class="mt-2 text-slate-400">{{ number_format($plan->price_vnd, 0, ',', '.') }} đ · {{ $plan->duration_days }} ngày</p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-xs font-bold uppercase tracking-[.18em]">
                        @if($isSelected)
                            <span class="rounded-full bg-emerald-400/15 px-3 py-2 text-emerald-100">
                                Đang mở
                            </span>
                        @endif
                        <span class="rounded-full px-3 py-2 {{ $plan->bank_qr_enabled ? 'bg-violet-500/20 text-violet-100' : 'bg-white/5 text-slate-500' }}">
                            QR {{ $plan->bank_qr_enabled ? 'ON' : 'OFF' }}
                        </span>
                        <span class="rounded-full px-3 py-2 {{ $plan->wallet_enabled ? 'bg-emerald-500/20 text-emerald-100' : 'bg-white/5 text-slate-500' }}">
                            Ví {{ $plan->wallet_enabled ? 'ON' : 'OFF' }}
                        </span>
                    </div>
                </div>

                <form method="post" action="{{ route('admin.plans.update', $plan) }}" class="grid gap-5">
                    @csrf
                    @method('put')

                    <div class="grid gap-4 xl:grid-cols-4">
                        <label class="grid gap-2 xl:col-span-1">
                            <span class="text-sm text-slate-400">Tên gói</span>
                            <input class="premium-input" name="name" value="{{ old('name', $plan->name) }}" required>
                        </label>
                        <label class="grid gap-2 xl:col-span-1">
                            <span class="text-sm text-slate-400">Số ngày sử dụng</span>
                            <input class="premium-input" name="duration_days" type="number" min="1" max="5000" value="{{ old('duration_days', $plan->duration_days) }}" required>
                        </label>
                        <label class="grid gap-2 xl:col-span-1">
                            <span class="text-sm text-slate-400">Giá gói</span>
                            <input class="premium-input" name="price_vnd" type="text" inputmode="numeric" autocomplete="off" pattern="[0-9.]*" data-currency-input value="{{ old('price_vnd', number_format($plan->price_vnd, 0, ',', '.')) }}" required>
                        </label>
                        <div class="grid gap-3 xl:col-span-1">
                            <span class="text-sm text-slate-400">Phương thức thanh toán</span>
                            <label class="flex items-center justify-between rounded-2xl border border-white/10 bg-black/25 px-4 py-3">
                                <span class="text-sm font-semibold text-white">Mã QR ngân hàng</span>
                                <span class="flex items-center gap-2 text-sm text-slate-300">
                                    <input type="hidden" name="bank_qr_enabled" value="0">
                                    <input type="checkbox" name="bank_qr_enabled" value="1" class="h-4 w-4 rounded border-white/10 bg-black/40 text-violet-400" @checked(old('bank_qr_enabled', $plan->bank_qr_enabled))>
                                </span>
                            </label>
                            <label class="flex items-center justify-between rounded-2xl border border-white/10 bg-black/25 px-4 py-3">
                                <span class="text-sm font-semibold text-white">Thanh toán ví</span>
                                <span class="flex items-center gap-2 text-sm text-slate-300">
                                    <input type="hidden" name="wallet_enabled" value="0">
                                    <input type="checkbox" name="wallet_enabled" value="1" class="h-4 w-4 rounded border-white/10 bg-black/40 text-emerald-400" @checked(old('wallet_enabled', $plan->wallet_enabled))>
                                </span>
                            </label>
                        </div>
                    </div>

                    <label class="grid gap-2">
                        <span class="text-sm text-slate-400">Mô tả gói</span>
                        <textarea class="premium-input min-h-28" name="description" maxlength="1000" placeholder="Mô tả ngắn để hiển thị trên trang nâng cấp">{{ old('description', $plan->description) }}</textarea>
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm text-slate-400">Tính năng gói</span>
                        <textarea class="premium-input min-h-36" name="features_text" maxlength="3000" placeholder="Mỗi dòng là một tính năng">{{ old('features_text', implode(PHP_EOL, $plan->billingFeatures())) }}</textarea>
                        <span class="text-xs text-slate-500">Mỗi dòng sẽ hiển thị thành một bullet ở trang nâng cấp.</span>
                    </label>

                    <div class="flex flex-wrap justify-end gap-3">
                        <a href="{{ route('admin.plans.show', $plan) }}" class="rounded-2xl border border-white/10 px-5 py-4 font-black text-slate-200 transition hover:bg-white/10">
                            Mở riêng gói này
                        </a>
                        <button class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                            Lưu cấu hình gói
                        </button>
                    </div>
                </form>
            </article>
        @endforeach
    </section>
</div>

<script>
    document.querySelectorAll('[data-currency-input]').forEach((input) => {
        const formatVnd = () => {
            const digits = input.value.replace(/\D/g, '');
            input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        };

        input.addEventListener('input', formatVnd);
        input.addEventListener('paste', () => requestAnimationFrame(formatVnd));
        formatVnd();
    });
</script>
@endsection
