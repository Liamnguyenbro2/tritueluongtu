@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(14,165,233,.2),transparent_30%),radial-gradient(circle_at_18%_72%,rgba(139,92,246,.35),transparent_30%)]"></div>
        <div class="relative">
            <p class="text-sm font-semibold uppercase tracking-[.24em] text-sky-200/80">Admin Command Center</p>
            <h1 class="mt-3 text-4xl font-black sm:text-6xl">Admin Dashboard</h1>
            <p class="mt-4 max-w-2xl text-slate-300">Theo dõi ví hệ thống, người dùng, thanh toán và yêu cầu rút tiền trong một giao diện cao cấp.</p>
        </div>
    </section>

    <section class="grid gap-5 md:grid-cols-3">
        @foreach($systemWallets as $wallet)
            <article class="glass rounded-[28px] p-5 transition hover:-translate-y-1 hover:shadow-glow">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-400">{{ $wallet->type }}</p>
                    <i data-lucide="vault" class="h-5 w-5 text-amber-200"></i>
                </div>
                <p class="mt-3 text-3xl font-black">{{ number_format($wallet->balance_vnd, 0, ',', '.') }} đ</p>
            </article>
        @endforeach
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/70">Notification Center</p>
                <h2 class="mt-2 text-2xl font-black">Thông báo dashboard</h2>
                <p class="mt-1 text-sm text-slate-400">Tạo thông báo cố định và thông báo theo đợt để user đọc, xác nhận và lưu lại lịch sử.</p>
            </div>
            <a href="{{ route('admin.notifications.index') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-amber-300 to-violet-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                <i data-lucide="megaphone" class="h-5 w-5"></i> Quản lý thông báo
            </a>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-5 flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/70">Branding</p>
                <h2 class="mt-2 text-2xl font-black">Logo và text thương hiệu</h2>
                <p class="mt-1 text-sm text-slate-400">Cập nhật logo và 2 dòng chữ hiển thị ở sidebar.</p>
            </div>
            <div class="flex items-center gap-3 rounded-[24px] border border-white/10 bg-black/25 p-3">
                @if(!empty($brandSettings['logo_url']))
                    <img src="{{ $brandSettings['logo_url'] }}" alt="{{ $brandSettings['name'] }}" class="h-12 w-12 rounded-2xl object-cover">
                @else
                    <div class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-amber-300 via-fuchsia-400 to-violet-600 shadow-glow">
                        <i data-lucide="sparkles" class="h-6 w-6 text-white"></i>
                    </div>
                @endif
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[.24em] text-amber-200/80">{{ $brandSettings['eyebrow'] }}</p>
                    <p class="text-lg font-bold">{{ $brandSettings['name'] }}</p>
                </div>
            </div>
        </div>

        <form method="post" action="{{ route('admin.branding.update') }}" enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-[1.2fr_.7fr_.7fr_auto]">
            @csrf
            @method('put')
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">URL logo</span>
                <input class="premium-input" name="brand_logo_url" value="{{ old('brand_logo_url', $brandSettings['logo_url']) }}" placeholder="https://... hoặc để trống dùng icon mặc định">
                <span class="text-xs text-slate-500">Hoặc tải ảnh logo mới bên dưới.</span>
                <input class="premium-input" name="brand_logo_file" type="file" accept="image/*">
            </label>
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">Dòng chữ nhỏ</span>
                <input class="premium-input" name="brand_eyebrow" value="{{ old('brand_eyebrow', $brandSettings['eyebrow']) }}" required>
            </label>
            <label class="grid gap-2">
                <span class="text-sm text-slate-400">Tên thương hiệu</span>
                <input class="premium-input" name="brand_name" value="{{ old('brand_name', $brandSettings['name']) }}" required>
            </label>
            <div class="flex items-end">
                <button class="w-full rounded-2xl bg-gradient-to-r from-amber-300 to-violet-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                    Lưu thương hiệu
                </button>
            </div>
        </form>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <div class="glass rounded-[32px] p-6">
            <div class="mb-5 flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-2xl font-black">User gần đây</h2>
                    <p class="mt-1 text-sm text-slate-400">Tìm theo email hoặc số điện thoại.</p>
                </div>
                <form method="get" action="{{ route('admin.index') }}" class="flex w-full gap-2 lg:w-[420px]">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                        <input class="premium-input pl-11" name="q" value="{{ $search }}" placeholder="Email hoặc số điện thoại">
                    </div>
                    <button class="rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-3 font-bold shadow-glow transition hover:-translate-y-0.5">Tìm</button>
                    @if($search !== '')
                        <a href="{{ route('admin.index') }}" class="grid place-items-center rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-bold text-slate-200 transition hover:bg-white/15">Xóa</a>
                    @endif
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr><th class="py-3">ID</th><th>Email</th><th>Report</th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                    @forelse($users as $user)
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4">#{{ $user->id }}</td>
                            <td>{{ $user->email }}</td>
                            <td><a class="rounded-xl bg-violet-400/10 px-3 py-2 text-violet-100 transition hover:bg-violet-400/20" href="{{ route('admin.users.report', $user) }}">Xem</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-10 text-center text-slate-400">Không tìm thấy user phù hợp.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="glass rounded-[32px] p-6">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-violet-200/70">Wallet Transfer</p>
                    <h2 class="mt-2 text-2xl font-black">Nạp số dư ví cho user</h2>
                    <p class="mt-1 text-sm text-slate-400">Tìm user bằng email hoặc số điện thoại, sau đó chuyển từ ví admin.</p>
                </div>
                <i data-lucide="send" class="h-6 w-6 text-emerald-200"></i>
            </div>

            <div class="mb-5 rounded-[24px] border border-white/10 bg-black/20 p-4">
                <p class="text-sm text-slate-400">Số dư ví admin có thể chuyển</p>
                <p class="mt-1 text-3xl font-black">{{ number_format($adminWallet->balance_vnd, 0, ',', '.') }} đ</p>
            </div>

            @if($search === '')
                <div class="rounded-[24px] border border-amber-200/20 bg-amber-300/10 p-4 text-sm leading-6 text-amber-100">
                    Nhập email hoặc số điện thoại ở ô tìm kiếm để chọn user nhận tiền.
                </div>
            @else
                <form method="post" action="{{ route('admin.wallet-transfer') }}" class="grid gap-4">
                    @csrf
                    <label class="grid gap-2">
                        <span class="text-sm text-slate-400">User nhận tiền</span>
                        <select class="premium-input" name="user_id" required>
                            <option value="">Chọn user</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">
                                    #{{ $user->id }} - {{ $user->name }} - {{ $user->email }} - {{ $user->phone }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-2">
                        <span class="text-sm text-slate-400">Số tiền chuyển</span>
                        <input class="premium-input" name="amount_vnd" type="text" inputmode="numeric" autocomplete="off" pattern="[0-9.]*" data-currency-input value="{{ old('amount_vnd') }}" placeholder="Tối đa {{ number_format($adminWallet->balance_vnd, 0, ',', '.') }} đ" required>
                    </label>
                    <button class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1">
                        Xác nhận chuyển
                    </button>
                </form>
            @endif

            <div class="mt-6">
                <h3 class="text-lg font-black">Lịch sử chuyển đi gần đây</h3>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full min-w-[520px] text-left text-sm">
                        <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                        <tr><th class="py-3">Thời gian</th><th>Số tiền</th><th>Ghi chú</th></tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                        @forelse($transferLogs as $entry)
                            <tr class="text-slate-300">
                                <td class="py-4">{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-rose-200">{{ number_format($entry->amount_vnd, 0, ',', '.') }} đ</td>
                                <td>{{ $entry->memoWithTimestamp() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-slate-400">Chưa có lịch sử chuyển ví.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="glass rounded-[32px] p-6">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-2xl font-black">Rút tiền</h2>
                <i data-lucide="banknote-arrow-down" class="h-6 w-6 text-emerald-200"></i>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                    <tr><th class="py-3">ID tài khoản</th><th>Email</th><th>Số tiền</th><th>Trạng thái</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                    @foreach($withdrawals as $withdrawal)
                        @php
                            $statusLabel = match ($withdrawal->status) {
                                'approved' => 'Đã duyệt',
                                'rejected' => 'Từ chối',
                                default => 'Chờ duyệt',
                            };
                            $statusClass = match ($withdrawal->status) {
                                'approved' => 'bg-emerald-400/10 text-emerald-100',
                                'rejected' => 'bg-rose-400/10 text-rose-100',
                                default => 'bg-amber-300/10 text-amber-100',
                            };
                        @endphp
                        <tr class="text-slate-300 transition hover:bg-white/[.04]">
                            <td class="py-4">#{{ $withdrawal->user_id }}</td>
                            <td>{{ $withdrawal->user?->email ?? 'Không xác định' }}</td>
                            <td>{{ number_format($withdrawal->amount_vnd, 0, ',', '.') }} đ</td>
                            <td><span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>
                                @if($withdrawal->status === 'pending')
                                    <div class="flex gap-2">
                                        <form method="post" action="{{ route('admin.withdrawals.approve', $withdrawal) }}">
                                            @csrf
                                            <button class="rounded-xl bg-emerald-400/10 px-3 py-2 text-emerald-100 transition hover:bg-emerald-400/20">Duyệt</button>
                                        </form>
                                        <button
                                            type="button"
                                            data-reject-withdrawal
                                            data-reject-action="{{ route('admin.withdrawals.reject', $withdrawal) }}"
                                            data-reject-user="#{{ $withdrawal->user_id }} - {{ $withdrawal->user?->email ?? 'Không xác định' }}"
                                            data-reject-amount="{{ number_format($withdrawal->amount_vnd, 0, ',', '.').' đ' }}"
                                            class="rounded-xl bg-rose-400/10 px-3 py-2 text-rose-100 transition hover:bg-rose-400/20"
                                        >
                                            Từ chối
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs font-semibold text-slate-500">Đã xử lý</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div id="withdraw-reject-modal" class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/75 p-4 backdrop-blur-xl">
                <form method="post" action="" class="glass w-full max-w-lg rounded-[32px] p-6">
                    @csrf
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[.22em] text-rose-200/80">Từ chối rút tiền</p>
                            <h3 class="mt-2 text-2xl font-black">Nhập lý do từ chối</h3>
                        </div>
                        <button type="button" class="grid h-11 w-11 place-items-center rounded-2xl bg-white/10 transition hover:bg-white/20" data-reject-close>
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>

                    <div class="mt-5 rounded-[24px] border border-rose-300/20 bg-rose-500/10 p-4 text-sm leading-6 text-rose-100">
                        Hệ thống sẽ từ chối yêu cầu và hoàn lại số tiền đã tạm giữ vào ví số dư của user.
                    </div>

                    <div class="mt-5 grid gap-3 rounded-[24px] border border-white/10 bg-black/25 p-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-400">User</span>
                            <span class="text-right font-semibold text-white" data-reject-user></span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-400">Số tiền</span>
                            <span class="font-semibold text-white" data-reject-amount></span>
                        </div>
                    </div>

                    <label class="mt-5 grid gap-2">
                        <span class="text-sm text-slate-400">Lý do từ chối</span>
                        <textarea class="premium-input min-h-28" name="admin_note" required placeholder="Nhập lý do để user biết vì sao yêu cầu bị từ chối"></textarea>
                    </label>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <button type="button" class="rounded-2xl border border-white/10 bg-white/10 px-5 py-3 font-bold transition hover:bg-white/15" data-reject-close>
                            Hủy
                        </button>
                        <button class="rounded-2xl bg-gradient-to-r from-rose-500 to-violet-500 px-5 py-3 font-black shadow-glow transition hover:-translate-y-1">
                            Xác nhận từ chối
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
    (() => {
        const sharedPoolCard = Array.from(document.querySelectorAll('section.grid.gap-5.md\\:grid-cols-3 > article'))
            .find((card) => card.textContent.includes('shared_pool'));

        if (sharedPoolCard) {
            sharedPoolCard.classList.add('cursor-pointer');
            sharedPoolCard.addEventListener('click', () => {
                window.location.href = @js(route('admin.shared-pool.history'));
            });

            const hint = document.createElement('p');
            hint.className = 'mt-2 text-xs uppercase tracking-[.2em] text-sky-200/70';
            hint.textContent = 'Xem lich su dong chia';
            sharedPoolCard.appendChild(hint);
        }

        const modal = document.getElementById('withdraw-reject-modal');
        if (!modal) return;
        const form = modal.querySelector('form');
        const userField = modal.querySelector('[data-reject-user]');
        const amountField = modal.querySelector('[data-reject-amount]');

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('grid');
        };

        document.querySelectorAll('[data-reject-withdrawal]').forEach((button) => {
            button.addEventListener('click', () => {
                form.action = button.dataset.rejectAction || '';
                userField.textContent = button.dataset.rejectUser || '';
                amountField.textContent = button.dataset.rejectAmount || '';
                modal.classList.remove('hidden');
                modal.classList.add('grid');
            });
        });

        modal.querySelectorAll('[data-reject-close]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    })();

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
