<?php

namespace App\Services;

use App\Mail\OtpEmailMessage;
use App\Models\SiteSetting;
use App\Models\SmtpSetting;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class DatabaseSmtpMailService
{
    public function activeSetting(): ?SmtpSetting
    {
        return SmtpSetting::active();
    }

    public function ensureActiveSetting(): SmtpSetting
    {
        $setting = $this->activeSetting();

        if (! $setting || ! $setting->gmail_address || ! $setting->app_password_encrypted) {
            throw new RuntimeException('Chưa có cấu hình SMTP đang hoạt động.');
        }

        return $setting;
    }

    public function applyRuntimeConfig(?SmtpSetting $setting = null): SmtpSetting
    {
        $setting = $setting ?? $this->ensureActiveSetting();
        $siteName = SiteSetting::branding()['name'] ?? config('app.name', 'Tri Tue Luong Tu');

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $setting->smtp_host);
        Config::set('mail.mailers.smtp.port', $setting->smtp_port);
        Config::set('mail.mailers.smtp.encryption', $setting->encryption);
        Config::set('mail.mailers.smtp.username', $setting->gmail_address);
        Config::set('mail.mailers.smtp.password', $setting->decryptedPassword());
        Config::set('mail.from.address', $setting->gmail_address);
        Config::set('mail.from.name', $siteName);

        /** @var MailManager $manager */
        $manager = app('mail.manager');

        if (method_exists($manager, 'forgetMailers')) {
            $manager->forgetMailers();
        }

        return $setting;
    }

    public function testConnection(?SmtpSetting $setting = null): void
    {
        $this->applyRuntimeConfig($setting);
        $mailer = Mail::mailer('smtp');
        $transport = $mailer->getSymfonyTransport();

        try {
            if (method_exists($transport, 'start')) {
                $transport->start();
            }
        } finally {
            if (method_exists($transport, 'stop')) {
                try {
                    $transport->stop();
                } catch (Throwable) {
                    // Ignore close failures after a successful start attempt.
                }
            }
        }
    }

    public function sendHtml(string $to, string $subject, string $html, ?SmtpSetting $setting = null): void
    {
        $this->applyRuntimeConfig($setting);

        Mail::mailer('smtp')
            ->to($to)
            ->send(new OtpEmailMessage($subject, $html));
    }
}
