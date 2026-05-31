<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_header_marquee_text(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->from('/admin/notifications')
            ->put('/admin/notifications/marquee', [
                'header_marquee_text' => 'Dong text chay moi cho header dashboard.',
            ])
            ->assertRedirect('/admin/notifications');

        $this->assertSame(
            'Dong text chay moi cho header dashboard.',
            SiteSetting::getValue('header_marquee_text')
        );

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Dong text chay moi cho header dashboard.');
    }
}
