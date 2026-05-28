@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_78%_18%,rgba(16,185,129,.24),transparent_32%),radial-gradient(circle_at_18%_82%,rgba(139,92,246,.26),transparent_30%)]"></div>
        <div class="relative flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-emerald-200/80">Admin Security</p>
                <h1 class="mt-3 text-4xl font-black sm:text-6xl">Đổi pass user</h1>
                <p class="mt-4 max-w-2xl text-slate-300">Admin tìm tài khoản bằng email, ID đăng nhập hoặc số điện thoại, sau đó nhập mật khẩu mới cho user.</p>
            </div>
            <i data-lucide="key-round" class="hidden h-12 w-12 text-emerald-200 lg:block"></i>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-5 flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-violet-200/70">User lookup</p>
                <h2 class="mt-2 text-2xl font-black">Tìm user cần đổi mật khẩu</h2>
            </div>
            <form method="get" action="{{ route('admin.passwords') }}" class="flex w-full gap-2 lg:w-[520px]">
                <div class="relative flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                    <input class="premium-input pl-11" name="q" value="{{ $search }}" placeholder="Email, ID đăng nhập hoặc số điện thoại">
                </div>
                <button class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-3 font-bold shadow-glow transition hover:-translate-y-0.5">Tìm</button>
            </form>
        </div>

        @if($search === '')
            <div class="rounded-[24px] border border-white/10 bg-black/20 p-5 text-sm leading-6 text-slate-300">
                Nhập email, ID đăng nhập hoặc số điện thoại để tìm user.
            </div>
        @elseif($users->isEmpty())
            <div class="rounded-[24px] border border-amber-200/20 bg-amber-300/10 p-5 text-sm leading-6 text-amber-100">
                Không tìm thấy user phù hợp với từ khóa "{{ $search }}".
            </div>
        @else
            <div class="grid gap-5">
                @foreach($users as $user)
                    <article class="rounded-[28px] border border-white/10 bg-black/20 p-5">
                        <div class="grid gap-5 lg:grid-cols-[1fr_.9fr] lg:items-start">
                            <div>
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm text-slate-400">User tìm thấy</p>
                                        <h3 class="mt-2 break-anywhere text-2xl font-black">{{ $user->name }}</h3>
                                    </div>
                                    <span class="rounded-full bg-violet-400/10 px-3 py-1 text-xs font-bold text-violet-100">#{{ $user->id }}</span>
                                </div>
                                <dl class="mt-5 grid gap-3 text-sm">
                                    <div class="grid gap-1 sm:grid-cols-[140px_1fr]">
                                        <dt class="text-slate-500">ID đăng nhập</dt>
                                        <dd class="break-anywhere font-semibold text-white">{{ $user->username }}</dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[140px_1fr]">
                                        <dt class="text-slate-500">Email</dt>
                                        <dd class="break-anywhere font-semibold text-white">{{ $user->email }}</dd>
                                    </div>
                                    <div class="grid gap-1 sm:grid-cols-[140px_1fr]">
                                        <dt class="text-slate-500">Số điện thoại</dt>
                                        <dd class="break-anywhere font-semibold text-white">{{ $user->phone }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <form method="post" action="{{ route('admin.passwords.update') }}" class="grid gap-4 rounded-[24px] border border-white/10 bg-white/[.04] p-4">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <label class="grid gap-2">
                                    <span class="text-sm text-slate-400">Mật khẩu mới</span>
                                    <input class="premium-input" name="password" type="password" autocomplete="new-password" required minlength="8">
                                </label>
                                <label class="grid gap-2">
                                    <span class="text-sm text-slate-400">Nhập lại mật khẩu mới</span>
                                    <input class="premium-input" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8">
                                </label>
                                <button class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1">
                                    Đổi mật khẩu
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
