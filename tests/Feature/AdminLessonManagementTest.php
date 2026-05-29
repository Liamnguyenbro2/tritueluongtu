<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminLessonManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_lesson_media_without_removing_lesson(): void
    {
        $this->seed();
        Storage::fake('local');
        Storage::fake('public');

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $course = Course::query()->firstOrFail();

        Storage::disk('local')->put('protected/lesson-media/test-video.mp4', 'fake-video-content');

        $lesson = Lesson::query()->create([
            'course_id' => $course->id,
            'position' => 999,
            'title' => 'Bài học test xóa media',
            'description' => 'Giữ nguyên bài học, chỉ xóa file media.',
            'media_type' => 'video',
            'media_path' => 'protected/lesson-media/test-video.mp4',
            'is_trial' => false,
            'duration_minutes' => 15,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.lessons.delete-media', $lesson))
            ->assertRedirect();

        $lesson->refresh();

        $this->assertSame('Bài học test xóa media', $lesson->title);
        $this->assertNull($lesson->media_type);
        $this->assertNull($lesson->media_path);
        Storage::disk('local')->assertMissing('protected/lesson-media/test-video.mp4');
    }
}
