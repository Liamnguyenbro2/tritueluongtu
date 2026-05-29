<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoiceSamplePromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_incomplete_user_sees_voice_sample_prompt_until_completed(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ghi âm giọng nói của bạn')
            ->assertSee('Bỏ qua lúc này');

        $user->profile()->updateOrCreate([], [
            'accepted_terms' => true,
            'accepted_terms_at' => now(),
            'voice_sample_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Ghi âm giọng nói của bạn');
    }

    public function test_user_can_upload_and_complete_voice_sample(): void
    {
        $this->seed();
        Storage::fake('local');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)
            ->post(route('voice-sample.store'), [
                'recording' => UploadedFile::fake()->create('voice.webm', 200, 'audio/webm'),
            ])
            ->assertOk()
            ->assertJsonStructure(['message', 'delete_after_at']);

        $profile = $user->profile()->firstOrFail();
        $this->assertNotNull($profile->voice_sample_path);
        Storage::disk('local')->assertExists($profile->voice_sample_path);
        $this->assertNotNull($profile->voice_sample_delete_after_at);
        $this->assertNull($profile->voice_sample_completed_at);

        $this->actingAs($user)
            ->post(route('voice-sample.complete'))
            ->assertOk()
            ->assertJson([
                'message' => 'Đã hoàn thành bước ghi âm tối ưu hóa bài học.',
            ]);

        $this->assertNotNull($profile->fresh()->voice_sample_completed_at);
    }

    public function test_mobile_mp4_voice_upload_is_accepted(): void
    {
        $this->seed();
        Storage::fake('local');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)
            ->post(route('voice-sample.store'), [
                'recording' => UploadedFile::fake()->create('voice.m4a', 200, 'video/mp4'),
            ])
            ->assertOk()
            ->assertJsonStructure(['message', 'delete_after_at']);

        $profile = $user->profile()->firstOrFail();
        $this->assertNotNull($profile->voice_sample_path);
        Storage::disk('local')->assertExists($profile->voice_sample_path);
    }

    public function test_purge_command_deletes_expired_voice_sample_file(): void
    {
        $this->seed();
        Storage::fake('local');
        Carbon::setTestNow('2026-05-29 10:00:00');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $profile = $user->profile()->firstOrFail();

        Storage::disk('local')->put('protected/voice-samples/test.webm', 'voice');

        $profile->update([
            'voice_sample_path' => 'protected/voice-samples/test.webm',
            'voice_sample_uploaded_at' => now()->subMinutes(16),
            'voice_sample_delete_after_at' => now()->subMinute(),
            'voice_sample_completed_at' => now()->subMinutes(15),
        ]);

        $this->artisan('voice-samples:purge')->assertSuccessful();

        Storage::disk('local')->assertMissing('protected/voice-samples/test.webm');
        $profile->refresh();
        $this->assertNull($profile->voice_sample_path);
        $this->assertNull($profile->voice_sample_uploaded_at);
        $this->assertNull($profile->voice_sample_delete_after_at);
        $this->assertNotNull($profile->voice_sample_completed_at);

        Carbon::setTestNow();
    }
}
