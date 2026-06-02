<?php

namespace Tests\Feature;

use App\Mail\OtpEmailMessage;
use App\Models\EmailTemplate;
use App\Models\SmtpSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminEmailOtpSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_save_smtp_settings(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->from('/admin/email-otp')
            ->put('/admin/email-otp/smtp', [
                'gmail_address' => 'mailer@gmail.com',
                'app_password' => 'abc12345xyz',
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'encryption' => 'tls',
                'is_active' => '1',
            ])
            ->assertRedirect('/admin/email-otp')
            ->assertSessionHas('status');

        $setting = SmtpSetting::query()->firstOrFail();
        $this->assertSame('mailer@gmail.com', $setting->gmail_address);
        $this->assertTrue($setting->is_active);
        $this->assertNotSame('abc12345xyz', $setting->app_password_encrypted);
        $this->assertSame('abc12345xyz', Crypt::decryptString($setting->app_password_encrypted));
    }

    public function test_non_super_admin_cannot_open_email_otp_settings(): void
    {
        $this->seed();

        $limitedAdmin = User::query()->create([
            'username' => 'financelead',
            'name' => 'Finance Lead',
            'email' => 'financelead@example.com',
            'phone' => '0912345678',
            'password' => 'password',
            'role' => 'admin',
            'is_admin' => false,
        ]);

        $this->actingAs($limitedAdmin)
            ->get('/admin/email-otp')
            ->assertForbidden();
    }

    public function test_template_cannot_be_saved_without_required_otp_variables(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->from('/admin/email-otp')
            ->put('/admin/email-otp/template', [
                'subject' => 'Email khôi phục mật khẩu',
                'content' => 'No variable here',
            ])
            ->assertRedirect('/admin/email-otp')
            ->assertSessionHasErrors('content');
    }

    public function test_super_admin_can_send_test_email_from_saved_template(): void
    {
        $this->seed();
        Mail::fake();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        SmtpSetting::query()->create([
            'gmail_address' => 'mailer@gmail.com',
            'app_password_encrypted' => Crypt::encryptString('abc12345xyz'),
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'encryption' => 'tls',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post('/admin/email-otp/send-test-email', [
                'test_email' => 'receiver@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertSent(OtpEmailMessage::class, function (OtpEmailMessage $mail) {
            return str_contains($mail->subjectLine, 'Khôi phục mật khẩu')
                && str_contains($mail->htmlContent, '123456');
        });

        $template = EmailTemplate::forgotPassword();
        $this->assertSame(EmailTemplate::FORGOT_PASSWORD, $template->template_key);
    }
}
