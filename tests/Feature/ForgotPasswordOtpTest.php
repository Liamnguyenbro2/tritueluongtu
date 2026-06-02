<?php

namespace Tests\Feature;

use App\Mail\OtpEmailMessage;
use App\Models\PasswordResetOtp;
use App\Models\SmtpSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_otp_by_email_and_is_moved_to_step_two(): void
    {
        $this->seed();
        Mail::fake();
        $this->createActiveSmtpSetting();

        $this->from('/forgot-password')
            ->post('/forgot-password/send-otp', [
                'email' => 'user@example.com',
            ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status');

        $this->assertDatabaseCount('password_reset_otps', 1);

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Bước 2/3')
            ->assertSee('Verify OTP')
            ->assertSee('Gửi lại OTP');

        Mail::assertSent(OtpEmailMessage::class);
    }

    public function test_email_is_limited_to_three_otps_per_fifteen_minutes(): void
    {
        $this->seed();
        Mail::fake();
        $this->createActiveSmtpSetting();
        Carbon::setTestNow('2026-06-02 09:00:00');

        $this->post('/forgot-password/send-otp', [
            'email' => 'user@example.com',
        ])->assertRedirect('/forgot-password');

        $this->travel(61)->seconds();
        $this->post('/forgot-password/send-otp', [
            'email' => 'user@example.com',
        ])->assertRedirect('/forgot-password');

        $this->travel(61)->seconds();
        $this->post('/forgot-password/send-otp', [
            'email' => 'user@example.com',
        ])->assertRedirect('/forgot-password');

        $this->travel(61)->seconds();
        $this->from('/forgot-password')
            ->post('/forgot-password/send-otp', [
                'email' => 'user@example.com',
            ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');

        Carbon::setTestNow();
    }

    public function test_ip_is_limited_to_five_otps_per_day(): void
    {
        $this->seed();
        Mail::fake();
        $this->createActiveSmtpSetting();

        for ($i = 1; $i <= 5; $i++) {
            User::query()->create([
                'username' => 'sample'.$i,
                'name' => 'Sample '.$i,
                'email' => "sample{$i}@example.com",
                'phone' => '09000000'.$i.$i,
                'password' => 'password',
                'role' => 'user',
            ]);

            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])
                ->post('/forgot-password/send-otp', [
                    'email' => "sample{$i}@example.com",
                ])
                ->assertRedirect('/forgot-password');

            $this->travel(61)->seconds();
        }

        User::query()->create([
            'username' => 'sample6',
            'name' => 'Sample 6',
            'email' => 'sample6@example.com',
            'phone' => '0900000066',
            'password' => 'password',
            'role' => 'user',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])
            ->from('/forgot-password')
            ->post('/forgot-password/send-otp', [
                'email' => 'sample6@example.com',
            ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');
    }

    public function test_user_can_verify_otp_and_reset_password_through_three_steps(): void
    {
        $this->seed();
        Mail::fake();
        $this->createActiveSmtpSetting();

        $this->post('/forgot-password/send-otp', [
            'email' => 'user@example.com',
        ])->assertRedirect('/forgot-password');

        $otp = null;

        Mail::assertSent(OtpEmailMessage::class, function (OtpEmailMessage $mail) use (&$otp) {
            preg_match('/\b(\d{6})\b/', $mail->htmlContent, $matches);
            $otp = $matches[1] ?? null;

            return true;
        });

        $this->assertNotNull($otp);

        $this->post('/forgot-password/verify-otp', [
            'email' => 'user@example.com',
            'otp' => $otp,
        ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status');

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Bước 3/3')
            ->assertSee('Reset Password');

        $this->post('/forgot-password/reset', [
            'password' => 'Newpass1@',
            'password_confirmation' => 'Newpass1@',
        ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status');

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Thành công')
            ->assertSee('Đăng nhập ngay');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Newpass1@', $user->fresh()->password));
    }

    public function test_expired_otp_cannot_be_used(): void
    {
        $this->seed();
        Mail::fake();
        $this->createActiveSmtpSetting();
        Carbon::setTestNow('2026-06-02 10:00:00');

        $this->post('/forgot-password/send-otp', [
            'email' => 'user@example.com',
        ])->assertRedirect('/forgot-password');

        $otp = null;

        Mail::assertSent(OtpEmailMessage::class, function (OtpEmailMessage $mail) use (&$otp) {
            preg_match('/\b(\d{6})\b/', $mail->htmlContent, $matches);
            $otp = $matches[1] ?? null;

            return true;
        });

        Carbon::setTestNow(now()->addMinutes(6));

        $this->from('/forgot-password')
            ->post('/forgot-password/verify-otp', [
                'email' => 'user@example.com',
                'otp' => $otp,
            ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('otp');

        $this->assertNotNull(PasswordResetOtp::query()->first()?->used_at);
        Carbon::setTestNow();
    }

    public function test_user_cannot_reset_password_without_verifying_otp(): void
    {
        $this->seed();

        $this->from('/forgot-password')
            ->post('/forgot-password/reset', [
                'password' => 'Newpass1@',
                'password_confirmation' => 'Newpass1@',
            ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('password');
    }

    public function test_resend_otp_is_blocked_for_sixty_seconds_for_same_email(): void
    {
        $this->seed();
        Mail::fake();
        $this->createActiveSmtpSetting();

        $this->post('/forgot-password/send-otp', [
            'email' => 'user@example.com',
        ])->assertRedirect('/forgot-password');

        $this->from('/forgot-password')
            ->post('/forgot-password/send-otp', [
                'email' => 'user@example.com',
            ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');
    }

    private function createActiveSmtpSetting(): void
    {
        SmtpSetting::query()->create([
            'gmail_address' => 'mailer@gmail.com',
            'app_password_encrypted' => Crypt::encryptString('abc12345xyz'),
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'encryption' => 'tls',
            'is_active' => true,
        ]);
    }
}
