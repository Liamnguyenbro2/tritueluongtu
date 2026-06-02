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

    public function test_admin_can_switch_lesson_to_embed_video_source(): void
    {
        $this->seed();
        Storage::fake('local');
        Storage::fake('public');

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $course = Course::query()->firstOrFail();

        Storage::disk('local')->put('protected/lesson-media/test-video.mp4', 'fake-video-content');

        $lesson = Lesson::query()->create([
            'course_id' => $course->id,
            'position' => 10,
            'title' => 'Bai hoc embed',
            'description' => 'Noi dung',
            'media_type' => 'video',
            'media_path' => 'protected/lesson-media/test-video.mp4',
            'video_source_type' => 'upload',
            'is_trial' => false,
            'duration_minutes' => 15,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.lessons.update', $lesson), [
                'course_id' => $course->id,
                'position' => 10,
                'title' => 'Bai hoc embed',
                'description' => 'Noi dung',
                'duration_minutes' => 15,
                'video_source_type' => 'embed',
                'embed_url' => 'https://media.tritueluongtu.com/wp-content/uploads/2026/06/ngu-sau.mp4',
            ])
            ->assertRedirect();

        $lesson->refresh();

        $this->assertSame('embed', $lesson->video_source_type);
        $this->assertSame('video', $lesson->media_type);
        $this->assertNull($lesson->media_path);
        $this->assertSame('https://media.tritueluongtu.com/wp-content/uploads/2026/06/ngu-sau.mp4', $lesson->embed_url);
        Storage::disk('local')->assertMissing('protected/lesson-media/test-video.mp4');
    }

    public function test_embed_player_route_returns_protected_player_for_admin(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $course = Course::query()->firstOrFail();

        $lesson = Lesson::query()->create([
            'course_id' => $course->id,
            'position' => 11,
            'title' => 'Bai hoc player',
            'description' => 'Noi dung',
            'media_type' => 'video',
            'video_source_type' => 'embed',
            'embed_url' => 'https://media.tritueluongtu.com/embed/ngu-sau',
            'is_trial' => false,
            'duration_minutes' => 15,
        ]);

        $this->actingAs($admin)
            ->get(route('lessons.player', $lesson))
            ->assertOk()
            ->assertHeader('Content-Security-Policy')
            ->assertSee('media.tritueluongtu.com/embed/ngu-sau', false);
    }

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
