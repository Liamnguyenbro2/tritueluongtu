<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoLogoutSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_is_logged_out_after_24_hours_of_inactivity(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)
            ->withSession([
                'auth_session_started_at' => now()->subHours(30)->toIso8601String(),
                'auth_session_last_activity_at' => now()->subHours(25)->toIso8601String(),
            ])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('session_expired_notice', fn (array $payload) => $payload['message'] === 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.');
    }

    public function test_regular_user_is_logged_out_after_seven_days_even_if_active(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)
            ->withSession([
                'auth_session_started_at' => now()->subDays(8)->toIso8601String(),
                'auth_session_last_activity_at' => now()->subMinutes(5)->toIso8601String(),
            ])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('session_expired_notice');
    }

    public function test_admin_is_logged_out_after_four_hours_of_inactivity(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->withSession([
                'auth_session_started_at' => now()->subHours(6)->toIso8601String(),
                'auth_session_last_activity_at' => now()->subHours(5)->toIso8601String(),
            ])
            ->get(route('admin.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('session_expired_notice');
    }

    public function test_admin_is_logged_out_after_twenty_four_hours_even_if_active(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->withSession([
                'auth_session_started_at' => now()->subHours(25)->toIso8601String(),
                'auth_session_last_activity_at' => now()->subMinutes(3)->toIso8601String(),
            ])
            ->get(route('admin.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('session_expired_notice');
    }

    public function test_heartbeat_extends_active_session(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        Carbon::setTestNow('2026-06-10 12:00:00');

        $response = $this->actingAs($user)
            ->withSession([
                'auth_session_started_at' => now()->subDays(1)->toIso8601String(),
                'auth_session_last_activity_at' => now()->subHours(23)->toIso8601String(),
            ])
            ->postJson(route('session.heartbeat'));

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ])
            ->assertJsonStructure([
                'session' => [
                    'warning_seconds',
                    'idle_expires_at',
                    'absolute_expires_at',
                    'expires_at',
                ],
            ]);

        Carbon::setTestNow();
    }

    public function test_expired_json_request_receives_backend_401_response(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)
            ->withSession([
                'auth_session_started_at' => now()->subDays(8)->toIso8601String(),
                'auth_session_last_activity_at' => now()->subMinutes(1)->toIso8601String(),
            ])
            ->postJson(route('session.heartbeat'))
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.',
                'session_expired' => true,
            ]);
    }
}
