@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(248,200,78,.22),transparent_30%),radial-gradient(circle_at_18%_72%,rgba(14,165,233,.25),transparent_30%)]"></div>
        <div class="relative flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.24em] text-amber-200/80">Notification Center</p>
                <h1 class="mt-3 text-4xl font-black sm:text-6xl">Thông báo</h1>
                <p class="mt-4 max-w-2xl text-slate-300">Quản lý thông báo cố định cho lần đăng nhập đầu tiên và thông báo theo đợt gửi tới user.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="rounded-[24px] border border-white/10 bg-black/25 px-5 py-4">
                    <p class="text-xs text-slate-400">Cố định</p>
                    <p class="mt-1 text-2xl font-black">1</p>
                </div>
                <div class="rounded-[24px] border border-white/10 bg-black/25 px-5 py-4">
                    <p class="text-xs text-slate-400">Theo đợt</p>
                    <p class="mt-1 text-2xl font-black">{{ $campaigns->count() }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1fr_1fr]">
        <div class="glass rounded-[32px] p-6">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-violet-200/70">Loại 1</p>
                    <h2 class="mt-2 text-2xl font-black">Thông báo cố định</h2>
                    <p class="mt-1 text-sm text-slate-400">User xác nhận một lần duy nhất. Sau khi đã đọc, thông báo này không tự bật lại.</p>
                </div>
                <i data-lucide="pin" class="h-6 w-6 text-amber-200"></i>
            </div>

            <form method="post" action="{{ route('admin.notifications.fixed.update') }}" enctype="multipart/form-data" class="grid gap-4">
                @csrf
                @method('put')
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">Tiêu đề</span>
                    <input class="premium-input" name="title" value="{{ old('title', $fixedAnnouncement->title) }}" maxlength="160" required>
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">Nội dung</span>
                    <textarea class="premium-input min-h-44" name="body" required>{{ old('body', $fixedAnnouncement->body) }}</textarea>
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">URL ảnh</span>
                    <input class="premium-input" name="image_url" value="{{ old('image_url', $fixedAnnouncement->image_url) }}" placeholder="https://... hoặc để trống">
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">Tải ảnh mới</span>
                    <input class="premium-input" name="image_file" type="file" accept="image/*">
                </label>
                @if($fixedAnnouncement->image_url)
                    <img src="{{ $fixedAnnouncement->image_url }}" alt="{{ $fixedAnnouncement->title }}" class="max-h-52 w-full rounded-[24px] object-cover">
                @endif
                <button class="rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                    Lưu thông báo cố định
                </button>
            </form>
        </div>

        <div class="glass rounded-[32px] p-6">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.22em] text-emerald-200/70">Loại 2</p>
                    <h2 class="mt-2 text-2xl font-black">Thông báo theo đợt</h2>
                    <p class="mt-1 text-sm text-slate-400">Thông báo đang mở sẽ hiện popup cho user chưa xác nhận đã đọc.</p>
                </div>
                <i data-lucide="send" class="h-6 w-6 text-emerald-200"></i>
            </div>

            <form method="post" action="{{ route('admin.notifications.campaigns.store') }}" enctype="multipart/form-data" class="grid gap-4">
                @csrf
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">Tiêu đề</span>
                    <input class="premium-input" name="title" value="{{ old('title') }}" maxlength="160" required placeholder="Ví dụ: Lịch bảo trì hệ thống">
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">Nội dung</span>
                    <textarea class="premium-input min-h-44" name="body" required placeholder="Nhập nội dung thông báo gửi tới user"></textarea>
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">URL ảnh</span>
                    <input class="premium-input" name="image_url" value="{{ old('image_url') }}" placeholder="https://... hoặc để trống">
                </label>
                <label class="grid gap-2">
                    <span class="text-sm text-slate-400">Tải ảnh</span>
                    <input class="premium-input" name="image_file" type="file" accept="image/*">
                </label>
                <label class="flex items-center justify-between gap-4 rounded-[24px] border border-white/10 bg-black/25 px-4 py-3">
                    <span>
                        <span class="block text-sm font-semibold text-white">Mở thông báo ngay</span>
                        <span class="block text-xs text-slate-400">Tắt đi nếu chỉ muốn lưu nháp trong lịch sử admin.</span>
                    </span>
                    <input type="checkbox" name="is_active" value="1" checked class="h-5 w-5 rounded border-white/20 bg-black/40 text-violet-500">
                </label>
                <button class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                    Tạo thông báo
                </button>
            </form>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <div class="mb-5 flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/70">History</p>
                <h2 class="mt-2 text-2xl font-black">Lịch sử thông báo</h2>
                <p class="mt-1 text-sm text-slate-400">Lưu số thông báo, nội dung tóm tắt và ngày tạo.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[.18em] text-slate-500">
                <tr>
                    <th class="py-3">Số thông báo</th>
                    <th>Loại</th>
                    <th>Nội dung text tóm tắt</th>
                    <th>Ngày tạo</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                @forelse($announcementHistory as $announcement)
                    <tr class="text-slate-300 transition hover:bg-white/[.04]">
                        <td class="py-4 font-semibold text-white">#{{ str_pad((string) $announcement->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $announcement->type === \App\Models\Announcement::TYPE_FIXED ? 'Cố định' : 'Theo đợt' }}</td>
                        <td class="max-w-md">{{ $announcement->summary(120) }}</td>
                        <td>{{ $announcement->created_at->format('d/m/Y | H:i') }}</td>
                        <td>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $announcement->is_active ? 'bg-emerald-400/10 text-emerald-100' : 'bg-slate-400/10 text-slate-300' }}">
                                {{ $announcement->is_active ? 'Đang mở' : 'Đang tắt' }}
                            </span>
                        </td>
                        <td>
                            @if($announcement->type === \App\Models\Announcement::TYPE_CAMPAIGN)
                                <form method="post" action="{{ route('admin.notifications.toggle', $announcement) }}">
                                    @csrf
                                    <button class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-bold text-slate-100 transition hover:bg-white/15">
                                        {{ $announcement->is_active ? 'Tắt' : 'Mở' }}
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-slate-500">Sửa ở form cố định</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-400">Chưa có lịch sử thông báo.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
