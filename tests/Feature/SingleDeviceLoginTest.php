<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserLoginSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleDeviceLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_login_when_active_session_exists_on_another_device(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        UserLoginSession::query()->create([
            'user_id' => $user->id,
            'session_id' => 'device-a-session',
            'device_name' => 'Windows - Chrome',
            'user_agent' => 'Mozilla/5.0',
            'ip_address' => '127.0.0.1',
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120)),
        ]);

        $this->from('/login')->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('device_login_conflict', function (array $payload) {
                return $payload['message'] === 'Bạn đang sử dụng tài khoản này trên một thiết bị khác, vui lòng đăng xuất khỏi thiết bị đó.'
                    && $payload['device_name'] === 'Windows - Chrome';
            });

        $this->assertGuest();
    }

    public function test_regular_user_can_login_again_after_previous_session_expires(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        UserLoginSession::query()->create([
            'user_id' => $user->id,
            'session_id' => 'expired-device-session',
            'device_name' => 'Android - Chrome',
            'user_agent' => 'Mozilla/5.0',
            'ip_address' => '127.0.0.1',
            'last_seen_at' => now()->subHours(3),
            'expires_at' => now()->subMinute(),
        ]);

        $this->post('/login', [
            'login' => $user->username,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame(
            'expired-device-session',
            UserLoginSession::query()->where('user_id', $user->id)->value('session_id')
        );
    }

    public function test_logout_clears_user_login_session(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('user_login_sessions', [
            'user_id' => $user->id,
        ]);

        $this->post('/logout')->assertRedirect(route('landing'));

        $this->assertDatabaseMissing('user_login_sessions', [
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_login_is_not_limited_to_single_device(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        UserLoginSession::query()->create([
            'user_id' => $admin->id,
            'session_id' => 'admin-device-a',
            'device_name' => 'Mac - Safari',
            'user_agent' => 'Mozilla/5.0',
            'ip_address' => '127.0.0.1',
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120)),
        ]);

        $this->post('/login', [
            'login' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.index'));

        $this->assertAuthenticatedAs($admin);
    }

}
