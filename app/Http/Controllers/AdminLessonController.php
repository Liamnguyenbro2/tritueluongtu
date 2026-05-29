<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        [$data['media_type'], $data['media_path']] = $this->storeMedia($request);

        Lesson::query()->create($data);

        return back()->with('status', 'Đã tạo nội dung khóa học.');
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('thumbnail')) {
            $this->deleteFile($lesson->thumbnail_path);
            $data['thumbnail_path'] = $this->storeUpload($request, 'thumbnail', 'protected/lesson-thumbnails');
        }

        if ($request->hasFile('media')) {
            $this->deleteFile($lesson->media_path);
            [$data['media_type'], $data['media_path']] = $this->storeMedia($request);
        }

        $lesson->update($data);

        return back()->with('status', 'Đã cập nhật nội dung khóa học.');
    }

    public function deleteMedia(Lesson $lesson): RedirectResponse
    {
        $this->deleteFile($lesson->media_path);

        $lesson->update([
            'media_type' => null,
            'media_path' => null,
        ]);

        return back()->with('status', 'Đã xóa media của bài học.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'position' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_trial' => ['nullable', 'boolean'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'thumbnail' => ['nullable', 'image', 'max:10240'],
            'media' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm', 'max:204800'],
        ]) + ['is_trial' => false];
    }

    private function storeMedia(Request $request): array
    {
        if (! $request->hasFile('media')) {
            return [null, null];
        }

        $file = $request->file('media');
        $type = str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';

        return [$type, $file->store('protected/lesson-media')];
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
}
