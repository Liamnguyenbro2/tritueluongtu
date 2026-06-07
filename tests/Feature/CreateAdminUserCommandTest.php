<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_create_command_creates_admin_profile_wallet_and_referral(): void
    {
        $this->seed();

        $this->artisan('admin:create', [
            'email' => 'admin2@tritueluongtu.com',
            'username' => 'admin2',
            'name' => 'Admin Hai',
            '--phone' => '0912345678',
            '--password' => 'Admin@123456',
        ])
            ->assertSuccessful()
            ->expectsOutput('Tạo tài khoản admin thành công.');

        $user = User::query()->where('email', 'admin2@tritueluongtu.com')->firstOrFail();

        $this->assertTrue($user->isAdmin());
        $this->assertSame('admin', $user->role);
        $this->assertSame('admin2', $user->username);
        $this->assertSame('0912345678', $user->phone);
        $this->assertNotNull($user->profile);
        $this->assertNotNull($user->referralLink);
        $this->assertNotNull($user->wallet);
        $this->assertTrue(password_verify('Admin@123456', $user->password));
    }
}
