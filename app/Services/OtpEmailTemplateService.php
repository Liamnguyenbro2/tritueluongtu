<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

class OtpEmailTemplateService
{
    public const REQUIRED_VARIABLES = [
        '{{otp}}',
        '{{expire_minutes}}',
    ];

    public function forgotPasswordTemplate(): EmailTemplate
    {
        return EmailTemplate::forgotPassword();
    }

    public function validateTemplateContent(string $subject, string $content): array
    {
        $errors = [];

        foreach (self::REQUIRED_VARIABLES as $variable) {
            if (! Str::contains($subject.' '.$content, $variable)) {
                $errors[] = "Template bắt buộc phải chứa {$variable}.";
            }
        }

        return $errors;
    }

    public function variables(string $otp, int $expireMinutes): array
    {
        $siteName = SiteSetting::branding()['name'] ?? config('app.name', 'Tri Tue Luong Tu');

        return [
            '{{otp}}' => $otp,
            '{{expire_minutes}}' => (string) $expireMinutes,
            '{{site_name}}' => $siteName,
            '{{current_year}}' => (string) now()->year,
        ];
    }

    public function render(EmailTemplate $template, string $otp, int $expireMinutes): array
    {
        $variables = $this->variables($otp, $expireMinutes);
        $subject = strtr($template->subject, $variables);
        $body = trim(strtr($template->content, $variables));

        return [
            'subject' => $subject,
            'text' => $body,
            'html' => $this->wrapHtml($body),
        ];
    }

    public function preview(string $subject, string $content): array
    {
        $template = new EmailTemplate([
            'subject' => $subject,
            'content' => $content,
        ]);

        return $this->render($template, '123456', 5);
    }

    public function restoreForgotPasswordTemplate(): EmailTemplate
    {
        $template = $this->forgotPasswordTemplate();
        $template->update([
            'subject' => EmailTemplate::DEFAULT_SUBJECT,
            'content' => EmailTemplate::DEFAULT_CONTENT,
        ]);

        return $template->fresh();
    }

    private function wrapHtml(string $body): string
    {
        $escaped = nl2br(e($body));

        return <<<HTML
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email OTP</title>
</head>
<body style="margin:0;padding:24px;background:#0b1020;font-family:Arial,sans-serif;color:#e2e8f0;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;border-collapse:collapse;">
    <tr>
      <td style="padding:32px;border:1px solid rgba(255,255,255,.08);border-radius:28px;background:linear-gradient(180deg,rgba(30,41,59,.96),rgba(15,23,42,.98));box-shadow:0 0 40px rgba(139,92,246,.18);">
        <div style="font-size:12px;letter-spacing:.24em;text-transform:uppercase;color:#c4b5fd;font-weight:700;">Tri Tue Luong Tu</div>
        <div style="margin-top:18px;font-size:16px;line-height:1.85;color:#e2e8f0;">{$escaped}</div>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }
}
