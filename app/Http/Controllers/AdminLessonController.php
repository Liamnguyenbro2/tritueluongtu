<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminLessonController extends Controller
{
    public function index(): View
    {
        return view('admin.lessons.index', [
            'courses' => Course::query()->orderBy('title')->get(),
            'lessons' => Lesson::query()->with('course')->orderBy('position')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $data['thumbnail_path'] = $this->storeUpload($request, 'thumbnail', 'protected/lesson-thumbnails');
        [$data['media_type'], $data['media_path'], $data['video_source_type'], $data['embed_url']] = $this->resolveMediaPayload($request);

        Lesson::query()->create($data);

        return back()->with('status', 'Đã tạo nội dung khóa học.');
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $data = $this->validated($request, $lesson);

        if ($request->hasFile('thumbnail')) {
            $this->deleteFile($lesson->thumbnail_path);
            $data['thumbnail_path'] = $this->storeUpload($request, 'thumbnail', 'protected/lesson-thumbnails');
        }

        if ($request->input('video_source_type', 'upload') === 'embed' && $lesson->media_path) {
            $this->deleteFile($lesson->media_path);
        }

        [$data['media_type'], $data['media_path'], $data['video_source_type'], $data['embed_url']] = $this->resolveMediaPayload($request, $lesson);

        $lesson->update($data);

        return back()->with('status', 'Đã cập nhật nội dung khóa học.');
    }

    public function deleteMedia(Lesson $lesson): RedirectResponse
    {
        $this->deleteFile($lesson->media_path);

        $lesson->update([
            'media_type' => null,
            'media_path' => null,
            'video_source_type' => null,
            'embed_url' => null,
        ]);

        return back()->with('status', 'Đã xóa media của bài học.');
    }

    private function validated(Request $request, ?Lesson $lesson = null): array
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'position' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_trial' => ['nullable', 'boolean'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'thumbnail' => ['nullable', 'image', 'max:10240'],
            'media' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm', 'max:204800'],
            'video_source_type' => ['required', 'in:upload,embed'],
            'embed_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $sourceType = $data['video_source_type'] ?? 'upload';
        $embedUrl = $this->normalizeEmbedUrl($data['embed_url'] ?? null);

        if ($sourceType === 'embed') {
            if ($embedUrl === null) {
                throw ValidationException::withMessages([
                    'embed_url' => 'Vui lòng nhập Embed URL từ Media Server.',
                ]);
            }

            if (! $this->isAllowedEmbedUrl($embedUrl)) {
                throw ValidationException::withMessages([
                    'embed_url' => 'Embed URL chỉ được phép từ media.tritueluongtu.com.',
                ]);
            }
        }

        if (
            $sourceType === 'upload'
            && $lesson?->video_source_type === 'embed'
            && ! $request->hasFile('media')
        ) {
            throw ValidationException::withMessages([
                'media' => 'Vui lòng upload video mới khi chuyển từ Link Nhúng sang Upload Video.',
            ]);
        }

        return $data + ['is_trial' => false, 'embed_url' => $embedUrl];
    }

    private function resolveMediaPayload(Request $request, ?Lesson $lesson = null): array
    {
        $sourceType = $request->input('video_source_type', 'upload');
        $embedUrl = $this->normalizeEmbedUrl((string) $request->input('embed_url', ''));

        if ($sourceType === 'embed') {
            return ['video', null, 'embed', $embedUrl];
        }

        if (! $request->hasFile('media')) {
            return [
                $lesson?->media_type,
                $lesson?->media_path,
                $lesson?->video_source_type === 'embed' ? null : $lesson?->video_source_type,
                null,
            ];
        }

        $this->deleteFile($lesson?->media_path);

        $file = $request->file('media');
        $type = str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';

        return [
            $type,
            $file->store('protected/lesson-media'),
            $type === 'video' ? 'upload' : null,
            null,
        ];
    }

    private function storeUpload(Request $request, string $field, string $directory): ?string
    {
        return $request->hasFile($field) ? $request->file($field)->store($directory) : null;
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
            Storage::disk('public')->delete($path);
        }
    }

    private function normalizeEmbedUrl(?string $url): ?string
    {
        $normalized = trim((string) $url);

        return $normalized === '' ? null : $normalized;
    }

    private function isAllowedEmbedUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = Str::lower((string) ($parts['host'] ?? ''));
        $scheme = Str::lower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return in_array($host, config('quantum.media_embed.allowed_hosts', []), true);
    }
}
