<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserLoginSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiDeviceLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_can_login_without_device_restriction(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        UserLoginSession::query()->create([
            'user_id' => $user->id,
            'session_id' => 'older-device-session',
            'device_name' => 'Windows - Chrome',
            'user_agent' => 'Mozilla/5.0',
            'ip_address' => '127.0.0.1',
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120)),
        ]);

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_still_works_normally(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('landing'));

        $this->assertGuest();
    }

    public function test_admin_login_still_redirects_to_admin_dashboard(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->post('/login', [
            'login' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.index'));

        $this->assertAuthenticatedAs($admin);
    }
}
