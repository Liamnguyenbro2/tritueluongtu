@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_18%,rgba(248,200,78,.22),transparent_30%),radial-gradient(circle_at_20%_80%,rgba(139,92,246,.32),transparent_34%)]"></div>
        <div class="relative">
            <p class="text-sm font-semibold uppercase tracking-[.24em] text-amber-200/80">Course Content Studio</p>
            <h1 class="mt-3 text-4xl font-black sm:text-6xl">Quản lý nội dung học</h1>
            <p class="mt-4 max-w-2xl text-slate-300">Admin có thể tạo mới, chỉnh sửa bài học, upload thumbnail và media hiển thị khi khách hàng bấm xem.</p>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <h2 class="text-2xl font-black">Tạo nội dung mới</h2>
        <form method="post" action="{{ route('admin.lessons.store') }}" enctype="multipart/form-data" class="mt-6 grid gap-4 lg:grid-cols-4">
            @csrf
            <select class="premium-input" name="course_id" required>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                @endforeach
            </select>
            <input class="premium-input" name="position" type="number" min="1" placeholder="Vị trí" required>
            <input class="premium-input lg:col-span-2" name="title" placeholder="Tên bài học" required>
            <textarea class="premium-input lg:col-span-4" name="description" rows="3" placeholder="Mô tả nội dung"></textarea>
            <input class="premium-input" name="duration_minutes" type="number" min="1" max="600" value="12" placeholder="Thời lượng phút" required>
            <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300">
                <input type="checkbox" name="is_trial" value="1" class="h-5 w-5 rounded border-white/20 bg-black/40 text-violet-500">
                Bài miễn phí
            </label>
            <label class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">
                <span class="mb-2 block text-xs uppercase tracking-[.18em] text-slate-500">Thumbnail</span>
                <input name="thumbnail" type="file" accept="image/*" class="block w-full text-sm text-slate-300 file:mr-3 file:rounded-xl file:border-0 file:bg-violet-500 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white">
            </label>
            <label class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">
                <span class="mb-2 block text-xs uppercase tracking-[.18em] text-slate-500">Media ảnh/video</span>
                <input name="media" type="file" accept="image/*,video/mp4,video/webm" class="block w-full text-sm text-slate-300 file:mr-3 file:rounded-xl file:border-0 file:bg-fuchsia-500 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white">
            </label>
            <button class="rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1 lg:col-span-4">Tạo nội dung</button>
        </form>
    </section>

    <section class="grid gap-5">
        @foreach($lessons as $lesson)
            <article class="glass rounded-[32px] p-6">
                <div class="grid gap-6 xl:grid-cols-[280px_1fr]">
                    <div class="overflow-hidden rounded-[24px] border border-white/10 bg-black/25">
                        @if($lesson->thumbnail_path)
                            <img src="{{ route('lessons.thumbnail', $lesson) }}" alt="{{ $lesson->title }}" class="h-48 w-full object-cover" draggable="false">
                        @else
                            <div class="grid h-48 place-items-center bg-gradient-to-br from-violet-500/20 to-amber-300/10 text-slate-400">
                                <i data-lucide="image" class="h-10 w-10"></i>
                            </div>
                        @endif
                        <div class="p-4">
                            <p class="text-xs uppercase tracking-[.18em] text-slate-500">Media preview</p>
                            @if($lesson->media_path && $lesson->media_type === 'video')
                                <video class="mt-3 w-full rounded-2xl" controls controlsList="nodownload noplaybackrate" disablepictureinpicture oncontextmenu="return false" src="{{ route('lessons.media', $lesson) }}"></video>
                            @elseif($lesson->media_path)
                                <img class="mt-3 w-full rounded-2xl object-cover" src="{{ route('lessons.media', $lesson) }}" alt="{{ $lesson->title }}" draggable="false">
                            @else
                                <p class="mt-3 text-sm text-slate-400">Chưa upload media.</p>
                            @endif
                        </div>
                    </div>

                    <form method="post" action="{{ route('admin.lessons.update', $lesson) }}" enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-4">
                        @csrf
                        @method('PUT')
                        <select class="premium-input" name="course_id" required>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected($lesson->course_id === $course->id)>{{ $course->title }}</option>
                            @endforeach
                        </select>
                        <input class="premium-input" name="position" type="number" min="1" value="{{ $lesson->position }}" required>
                        <input class="premium-input lg:col-span-2" name="title" value="{{ $lesson->title }}" required>
                        <textarea class="premium-input lg:col-span-4" name="description" rows="3">{{ $lesson->description }}</textarea>
                        <input class="premium-input" name="duration_minutes" type="number" min="1" max="600" value="{{ $lesson->duration_minutes }}" required>
                        <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300">
                            <input type="checkbox" name="is_trial" value="1" @checked($lesson->is_trial) class="h-5 w-5 rounded border-white/20 bg-black/40 text-violet-500">
                            Bài miễn phí
                        </label>
                        <label class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">
                            <span class="mb-2 block text-xs uppercase tracking-[.18em] text-slate-500">Đổi thumbnail</span>
                            <input name="thumbnail" type="file" accept="image/*" class="block w-full text-sm text-slate-300 file:mr-3 file:rounded-xl file:border-0 file:bg-violet-500 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white">
                        </label>
                        <label class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">
                            <span class="mb-2 block text-xs uppercase tracking-[.18em] text-slate-500">Đổi media</span>
                            <input name="media" type="file" accept="image/*,video/mp4,video/webm" class="block w-full text-sm text-slate-300 file:mr-3 file:rounded-xl file:border-0 file:bg-fuchsia-500 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white">
                        </label>
                        <button class="rounded-2xl border border-white/10 bg-white/10 px-5 py-4 font-bold transition hover:bg-white/15 lg:col-span-4">Lưu chỉnh sửa</button>
                    </form>
                </div>
            </article>
        @endforeach
    </section>
</div>
@endsection
