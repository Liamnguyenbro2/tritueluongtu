<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_video_auto_loop_control_text(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Tự động lặp lại khi toàn màn hình');
    }
}
