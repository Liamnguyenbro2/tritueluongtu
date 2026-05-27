@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_78%_18%,rgba(139,92,246,.38),transparent_32%),radial-gradient(circle_at_18%_82%,rgba(248,200,78,.14),transparent_28%)]"></div>
        <div class="relative flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-violet-200/70">Profile Center</p>
                <h1 class="mt-3 text-4xl font-black sm:text-6xl">{{ $user->name }}</h1>
                <p class="mt-4 max-w-2xl text-slate-300">Quản lý thông tin tài khoản, mật khẩu và dữ liệu ngân hàng dùng cho ví số dư.</p>
            </div>
            <div class="rounded-[24px] border border-white/10 bg-black/25 p-5">
                <p class="text-sm text-slate-400">ID tài khoản</p>
                <p class="mt-1 font-mono text-2xl font-black">{{ $user->username }}</p>
            </div>
        </div>
    </section>

    <section class="grid gap-5 lg:grid-cols-3">
        <div class="glass rounded-[28px] p-5">
            <p class="text-sm text-slate-400">Email</p>
            <p class="mt-2 break-all text-xl font-bold">{{ $user->email }}</p>
        </div>
        <div class="glass rounded-[28px] p-5">
            <p class="text-sm text-slate-400">Số điện thoại</p>
            <p class="mt-2 text-xl font-bold">{{ $user->phone }}</p>
        </div>
        <div class="glass rounded-[28px] p-5">
            <p class="text-sm text-slate-400">Trạng thái</p>
            <p class="mt-2 text-xl font-bold {{ $user->activeSuspension()->exists() ? 'text-rose-200' : 'text-emerald-200' }}">
                {{ $user->activeSuspension()->exists() ? 'Đang bị giới hạn' : 'Đang hoạt động' }}
            </p>
        </div>
    </section>

    <section class="grid gap-6">
        <div class="glass rounded-[32px] p-6">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-violet-200/70">Security</p>
                    <h2 class="mt-2 text-2xl font-black">Đổi mật khẩu</h2>
                </div>
                <i data-lucide="key-round" class="h-6 w-6 text-amber-200"></i>
            </div>

            <form method="post" action="{{ route('profile.password') }}" class="grid gap-4">
                @csrf
                @method('put')
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">Mật khẩu hiện tại</span>
                    <input class="premium-input" name="current_password" type="password" autocomplete="current-password" required>
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">Mật khẩu mới</span>
                    <input class="premium-input" name="password" type="password" autocomplete="new-password" required>
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">Nhập lại mật khẩu mới</span>
                    <input class="premium-input" name="password_confirmation" type="password" autocomplete="new-password" required>
                </label>
                <button class="rounded-2xl border border-amber-200/20 bg-amber-300/10 px-5 py-4 font-black text-amber-100 transition hover:bg-amber-300/15">
                    Cập nhật mật khẩu
                </button>
            </form>
        </div>
    </section>

    <section
        class="glass rounded-[32px] p-6"
        x-data="{ confirmBank: false, bankName: @js(old('bank_name', $bankAccount?->bank_name)), accountNumber: @js(old('account_number', $bankAccount?->account_number)), accountHolder: @js(old('account_holder', $bankAccount?->account_holder)) }"
    >
        <div class="mb-5 flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-violet-200/70">Bank</p>
                <h2 class="mt-2 text-2xl font-black">Thông tin ngân hàng</h2>
                @if($bankAccount && ! $bankAccount->can_edit)
                    <p class="mt-2 text-sm font-semibold text-amber-100">Bạn không được phép thay đổi thông tin tài khoản ngân hàng.</p>
                @endif
            </div>
            <div class="rounded-full px-3 py-1 text-xs font-bold {{ $bankAccount && ! $bankAccount->can_edit ? 'bg-amber-300/10 text-amber-100' : 'bg-emerald-400/10 text-emerald-100' }}">
                {{ $bankAccount && ! $bankAccount->can_edit ? 'Đã khóa chỉnh sửa' : 'Có thể cập nhật' }}
            </div>
        </div>

        <form x-ref="bankForm" method="post" action="{{ route('profile.bank-account') }}" class="grid gap-4 md:grid-cols-3" @submit.prevent="confirmBank = true">
            @csrf
            @method('put')
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">Tên ngân hàng</span>
                <input class="premium-input" name="bank_name" x-model="bankName" value="{{ old('bank_name', $bankAccount?->bank_name) }}" required @disabled($bankAccount && ! $bankAccount->can_edit)>
            </label>
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">Số tài khoản</span>
                <input class="premium-input" name="account_number" x-model="accountNumber" value="{{ old('account_number', $bankAccount?->account_number) }}" required @disabled($bankAccount && ! $bankAccount->can_edit)>
            </label>
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">Chủ tài khoản</span>
                <input class="premium-input" name="account_holder" x-model="accountHolder" value="{{ old('account_holder', $bankAccount?->account_holder) }}" required @disabled($bankAccount && ! $bankAccount->can_edit)>
            </label>
            <div class="md:col-span-3">
                @if($bankAccount && ! $bankAccount->can_edit)
                    <p class="text-sm text-slate-400">Thông tin ngân hàng đã được lưu. Nếu cần sửa, admin có thể mở khóa chỉnh sửa cho tài khoản.</p>
                @else
                    <button class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-6 py-4 font-black shadow-glow transition hover:-translate-y-1">
                        Lưu thông tin ngân hàng
                    </button>
                @endif
            </div>
        </form>

        <template x-teleport="body">
        <div x-show="confirmBank" x-cloak x-transition.opacity class="fixed inset-0 z-[9999] flex items-start justify-center overflow-y-auto bg-black/80 px-4 py-6 backdrop-blur-xl sm:items-center" @click.self="confirmBank = false">
            <div x-transition.scale class="glass w-full max-w-lg rounded-[28px] p-5 sm:rounded-[32px] sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/80">Xác nhận ngân hàng</p>
                        <h3 class="mt-2 text-2xl font-black">Kiểm tra lại thông tin</h3>
                    </div>
                    <button type="button" class="grid h-11 w-11 place-items-center rounded-2xl bg-white/10 transition hover:bg-white/20" @click="confirmBank = false">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <div class="mt-5 rounded-[24px] border border-amber-200/20 bg-amber-300/10 p-4 text-sm leading-6 text-amber-100">
                    Thông tin này chỉ được nhập một lần duy nhất và không được phép thay đổi sau khi xác nhận.
                </div>

                <div class="mt-5 grid gap-3 rounded-[24px] border border-white/10 bg-black/25 p-4 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-400">Tên ngân hàng</span>
                        <span class="font-semibold text-white" x-text="bankName"></span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-400">Số tài khoản</span>
                        <span class="font-semibold text-white" x-text="accountNumber"></span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-400">Chủ tài khoản</span>
                        <span class="font-semibold text-white" x-text="accountHolder"></span>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <button type="button" class="rounded-2xl border border-white/10 bg-white/10 px-5 py-3 font-bold transition hover:bg-white/15" @click="confirmBank = false">
                        Sửa lại
                    </button>
                    <button type="button" class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-3 font-black shadow-glow transition hover:-translate-y-1" @click="$refs.bankForm.submit()">
                        Xác nhận lưu
                    </button>
                </div>
            </div>
        </div>
        </template>
    </section>
</div>
@endsection
