@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[.06] p-6 shadow-glow backdrop-blur-2xl sm:p-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_18%,rgba(248,200,78,.22),transparent_30%),radial-gradient(circle_at_20%_80%,rgba(139,92,246,.32),transparent_34%)]"></div>
        <div class="relative">
            <p class="text-sm font-semibold uppercase tracking-[.24em] text-amber-200/80">Course Content Studio</p>
            <h1 class="mt-3 text-4xl font-black sm:text-6xl">{!! html_entity_decode('Qu&#7843;n l&#253; n&#7897;i dung h&#7885;c') !!}</h1>
            <p class="mt-4 max-w-3xl text-slate-300">
                {!! html_entity_decode('Admin c&#243; th&#7875; upload media c&#361; ho&#7863;c ph&#225;t video t&#7915; Media Server ri&#234;ng qua Link Nh&#250;ng, gi&#250;p gi&#7843;m t&#7843;i hosting Laravel v&#224; qu&#7843;n l&#253; th&#432; vi&#7879;n video linh ho&#7841;t h&#417;n.') !!}
            </p>
        </div>
    </section>

    <section class="glass rounded-[32px] p-6">
        <h2 class="text-2xl font-black">{!! html_entity_decode('T&#7841;o n&#7897;i dung m&#7899;i') !!}</h2>
        <form method="post" action="{{ route('admin.lessons.store') }}" enctype="multipart/form-data" class="mt-6 grid gap-6 xl:grid-cols-[280px_1fr]" data-lesson-source-form>
            @csrf
            <div class="overflow-hidden rounded-[24px] border border-white/10 bg-black/25">
                <div class="grid h-48 place-items-center bg-gradient-to-br from-violet-500/20 to-amber-300/10 text-slate-400">
                    <i data-lucide="image" class="h-10 w-10"></i>
                </div>
                <div class="p-4">
                    <p class="text-xs uppercase tracking-[.18em] text-slate-500">Media Preview</p>
                    <div class="mt-3 rounded-2xl border border-white/10 bg-black/20 p-3" data-embed-preview-shell hidden>
                        <video class="hidden w-full rounded-2xl" controls controlsList="nodownload noplaybackrate noremoteplayback" disablepictureinpicture oncontextmenu="return false" data-embed-preview-video></video>
                        <iframe class="hidden aspect-video w-full rounded-2xl bg-black" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" data-embed-preview-iframe></iframe>
                    </div>
                    <p class="mt-3 text-sm text-slate-400" data-embed-preview-placeholder>{!! html_entity_decode('Nh&#7853;p Embed URL &#273;&#7875; xem tr&#432;&#7899;c video t&#7915; Media Server.') !!}</p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-4">
                <select class="premium-input" name="course_id" required>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                    @endforeach
                </select>
                <input class="premium-input" name="position" type="number" min="1" placeholder="{!! html_entity_decode('V&#7883; tr&#237;') !!}" required>
                <input class="premium-input lg:col-span-2" name="title" placeholder="{!! html_entity_decode('T&#234;n b&#224;i h&#7885;c') !!}" required>
                <textarea class="premium-input lg:col-span-4" name="description" rows="3" placeholder="{!! html_entity_decode('M&#244; t&#7843; n&#7897;i dung') !!}"></textarea>
                <input class="premium-input" name="duration_minutes" type="number" min="1" max="600" value="12" placeholder="{!! html_entity_decode('Th&#7901;i l&#432;&#7907;ng ph&#250;t') !!}" required>
                <input class="premium-input" name="unlock_price_vnd" type="number" min="0" step="1000" value="{{ config('quantum.default_lesson_unlock_price_vnd') }}" placeholder="{!! html_entity_decode('Gi&#225; m&#7903; kh&#243;a b&#224;i h&#7885;c') !!}" required>
                <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300">
                    <input type="checkbox" name="is_trial" value="1" class="h-5 w-5 rounded border-white/20 bg-black/40 text-violet-500">
                    {!! html_entity_decode('B&#224;i mi&#7877;n ph&#237;') !!}
                </label>
                <label class="grid gap-2 rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">
                    <span class="text-xs uppercase tracking-[.18em] text-slate-500">{!! html_entity_decode('Ngu&#7891;n video') !!}</span>
                    <select class="premium-input" name="video_source_type" data-video-source-select>
                        <option value="upload">Upload Video</option>
                        <option value="embed">Link Nhúng (Embed URL)</option>
                    </select>
                </label>
                <label class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">
                    <span class="mb-2 block text-xs uppercase tracking-[.18em] text-slate-500">Thumbnail</span>
                    <input name="thumbnail" type="file" accept="image/*" class="block w-full text-sm text-slate-300 file:mr-3 file:rounded-xl file:border-0 file:bg-violet-500 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white">
                </label>
                <label class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300" data-upload-field>
                    <span class="mb-2 block text-xs uppercase tracking-[.18em] text-slate-500">{!! html_entity_decode('Upload media') !!}</span>
                    <input name="media" type="file" accept="image/*,video/mp4,video/webm" class="block w-full text-sm text-slate-300 file:mr-3 file:rounded-xl file:border-0 file:bg-fuchsia-500 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white">
                </label>
                <label class="hidden rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300 lg:col-span-4" data-embed-field>
                    <span class="mb-2 block text-xs uppercase tracking-[.18em] text-slate-500">Embed URL</span>
                    <input
                        class="premium-input"
                        name="embed_url"
                        placeholder="https://media.tritueluongtu.com/wp-content/uploads/2026/06/ngu-sau.mp4"
                        data-embed-url-input
                    >
                    <span class="mt-2 text-xs text-slate-500">
                        {!! html_entity_decode('Ch&#7881; h&#7895; tr&#7907; media.tritueluongtu.com. C&#243; th&#7875; d&#249;ng link MP4 ho&#7863;c link player /embed/.') !!}
                    </span>
                </label>
                <button class="rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 px-5 py-4 font-black shadow-glow transition hover:-translate-y-1 lg:col-span-4">
                    {!! html_entity_decode('T&#7841;o n&#7897;i dung') !!}
                </button>
            </div>
        </form>
    </section>

    <section class="grid gap-5">
        @foreach($lessons as $lesson)
            @php
                $sourceType = $lesson->video_source_type === 'embed' ? 'embed' : 'upload';
            @endphp
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
                            <p class="text-xs uppercase tracking-[.18em] text-slate-500">Media Preview</p>
                            @if($lesson->video_source_type === 'embed' && $lesson->embed_url)
                                <iframe
                                    class="mt-3 aspect-video w-full rounded-2xl bg-black"
                                    src="{{ route('lessons.player', $lesson) }}"
                                    allow="autoplay; fullscreen; picture-in-picture"
                                    allowfullscreen
                                    referrerpolicy="strict-origin-when-cross-origin"
                                ></iframe>
                            @elseif($lesson->media_path && $lesson->media_type === 'video')
                                <video class="mt-3 w-full rounded-2xl" controls controlsList="nodownload noplaybackrate noremoteplayback" disablepictureinpicture oncontextmenu="return false" src="{{ route('lessons.media', $lesson) }}"></video>
                            @elseif($lesson->media_path)
                                <img class="mt-3 w-full rounded-2xl object-cover" src="{{ route('lessons.media', $lesson) }}" alt="{{ $lesson->title }}" draggable="false">
                            @else
                                <p class="mt-3 text-sm text-slate-400">{!! html_entity_decode('Ch&#432;a c&#243; media.') !!}</p>
                            @endif

                            @if($lesson->media_path || $lesson->embed_url)
                                <form method="post" action="{{ route('admin.lessons.delete-media', $lesson) }}" class="mt-4" onsubmit="return confirm('Bạn có chắc muốn xóa media của bài học này không?');">
                                    @csrf
                                    <button class="w-full rounded-2xl border border-rose-400/20 bg-rose-500/10 px-4 py-3 text-sm font-bold text-rose-100 transition hover:bg-rose-500/20">
                                        {!! html_entity_decode('X&#243;a media') !!}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <form method="post" action="{{ route('admin.lessons.update', $lesson) }}" enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-4" data-lesson-source-form>
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
                        <input class="premium-input" name="unlock_price_vnd" type="number" min="0" step="1000" value="{{ $lesson->unlock_price_vnd }}" required>
                        <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300">
                            <input type="checkbox" name="is_trial" value="1" @checked($lesson->is_trial) class="h-5 w-5 rounded border-white/20 bg-black/40 text-violet-500">
                            {!! html_entity_decode('B&#224;i mi&#7877;n ph&#237;') !!}
                        </label>
                        <label class="grid gap-2 rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">
                            <span class="text-xs uppercase tracking-[.18em] text-slate-500">{!! html_entity_decode('Ngu&#7891;n video') !!}</span>
                            <select class="premium-input" name="video_source_type" data-video-source-select>
                                <option value="upload" @selected($sourceType === 'upload')>Upload Video</option>
                                <option value="embed" @selected($sourceType === 'embed')>Link Nhúng (Embed URL)</option>
                            </select>
                        </label>
                        <label class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">
                            <span class="mb-2 block text-xs uppercase tracking-[.18em] text-slate-500">{!! html_entity_decode('&#272;&#7893;i thumbnail') !!}</span>
                            <input name="thumbnail" type="file" accept="image/*" class="block w-full text-sm text-slate-300 file:mr-3 file:rounded-xl file:border-0 file:bg-violet-500 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white">
                        </label>
                        <label class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300" data-upload-field @if($sourceType === 'embed') hidden @endif>
                            <span class="mb-2 block text-xs uppercase tracking-[.18em] text-slate-500">{!! html_entity_decode('&#272;&#7893;i media') !!}</span>
                            <input name="media" type="file" accept="image/*,video/mp4,video/webm" class="block w-full text-sm text-slate-300 file:mr-3 file:rounded-xl file:border-0 file:bg-fuchsia-500 file:px-4 file:py-2 file:text-sm file:font-bold file:text-white">
                        </label>
                        <label class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300 lg:col-span-4 @if($sourceType !== 'embed') hidden @endif" data-embed-field>
                            <span class="mb-2 block text-xs uppercase tracking-[.18em] text-slate-500">Embed URL</span>
                            <input
                                class="premium-input"
                                name="embed_url"
                                value="{{ $lesson->embed_url }}"
                                placeholder="https://media.tritueluongtu.com/wp-content/uploads/2026/06/ngu-sau.mp4"
                                data-embed-url-input
                            >
                            <span class="mt-2 text-xs text-slate-500">
                                {!! html_entity_decode('Ch&#7881; h&#7895; tr&#7907; media.tritueluongtu.com. C&#243; th&#7875; d&#249;ng link MP4 ho&#7863;c link player /embed/.') !!}
                            </span>
                        </label>
                        <button class="rounded-2xl border border-white/10 bg-white/10 px-5 py-4 font-bold transition hover:bg-white/15 lg:col-span-4">
                            {!! html_entity_decode('L&#432;u ch&#7881;nh s&#7917;a') !!}
                        </button>
                    </form>
                </div>
            </article>
        @endforeach
    </section>
</div>

<script>
    (() => {
        const resolvePreviewMode = (url) => {
            const normalized = String(url || '').trim().toLowerCase();

            if (!normalized) {
                return null;
            }

            return /\.(mp4|webm|ogg|m4v|mov)(\?.*)?$/.test(normalized) ? 'video' : 'iframe';
        };

        document.querySelectorAll('[data-lesson-source-form]').forEach((form) => {
            const sourceSelect = form.querySelector('[data-video-source-select]');
            const uploadField = form.querySelector('[data-upload-field]');
            const embedField = form.querySelector('[data-embed-field]');
            const embedInput = form.querySelector('[data-embed-url-input]');
            const previewShell = form.querySelector('[data-embed-preview-shell]');
            const previewVideo = form.querySelector('[data-embed-preview-video]');
            const previewIframe = form.querySelector('[data-embed-preview-iframe]');
            const previewPlaceholder = form.querySelector('[data-embed-preview-placeholder]');

            if (!sourceSelect) {
                return;
            }

            const syncPreview = () => {
                if (!previewShell || !previewVideo || !previewIframe) {
                    return;
                }

                const mode = resolvePreviewMode(embedInput?.value);
                const isEmbed = sourceSelect.value === 'embed';

                previewShell.hidden = !isEmbed || !mode;
                previewPlaceholder && (previewPlaceholder.hidden = isEmbed && !!mode);
                previewVideo.classList.add('hidden');
                previewIframe.classList.add('hidden');
                previewVideo.removeAttribute('src');
                previewIframe.removeAttribute('src');

                if (!isEmbed || !mode) {
                    previewVideo.load();
                    return;
                }

                if (mode === 'video') {
                    previewVideo.src = embedInput.value.trim();
                    previewVideo.classList.remove('hidden');
                    previewVideo.load();
                    return;
                }

                previewIframe.src = embedInput.value.trim();
                previewIframe.classList.remove('hidden');
            };

            const syncSource = () => {
                const isEmbed = sourceSelect.value === 'embed';

                if (uploadField) {
                    uploadField.hidden = isEmbed;
                    uploadField.classList.toggle('hidden', isEmbed);
                }

                if (embedField) {
                    embedField.hidden = !isEmbed;
                    embedField.classList.toggle('hidden', !isEmbed);
                }

                syncPreview();
            };

            sourceSelect.addEventListener('change', syncSource);
            embedInput?.addEventListener('input', syncPreview);
            syncSource();
        });
    })();
</script>
@endsection
