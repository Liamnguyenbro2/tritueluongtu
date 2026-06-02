<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\SmtpSetting;
use App\Services\DatabaseSmtpMailService;
use App\Services\OtpEmailTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminEmailOtpController extends Controller
{
    public function index(Request $request, OtpEmailTemplateService $templates): View
    {
        $this->ensureSuperAdmin($request);
        $smtp = SmtpSetting::current();
        $template = $templates->forgotPasswordTemplate();
        $preview = $templates->preview($template->subject, $template->content);

        return view('admin.email-otp', [
            'smtp' => $smtp,
            'template' => $template,
            'preview' => $preview,
            'templateVariables' => [
                '{{otp}}',
                '{{expire_minutes}}',
                '{{site_name}}',
                '{{current_year}}',
            ],
        ]);
    }

    public function updateSmtp(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        $smtp = SmtpSetting::current();
        $data = $request->validate([
            'gmail_address' => ['required', 'email'],
            'app_password' => [$smtp?->app_password_encrypted ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            'smtp_host' => ['required', 'string', 'max:120'],
            'smtp_port' => ['required', 'integer', 'between:1,65535'],
            'encryption' => ['required', 'in:tls'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'gmail_address' => strtolower(trim($data['gmail_address'])),
            'app_password_encrypted' => filled($data['app_password'] ?? null)
                ? Crypt::encryptString($data['app_password'])
                : $smtp?->app_password_encrypted,
            'smtp_host' => trim($data['smtp_host']),
            'smtp_port' => (int) $data['smtp_port'],
            'encryption' => $data['encryption'],
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($smtp) {
            $smtp->update($payload);
        } else {
            $smtp = SmtpSetting::query()->create($payload);
        }

        return back()
            ->with('status', html_entity_decode('&#272;&#227; l&#432;u c&#7845;u h&#236;nh SMTP Gmail.'))
            ->with('smtp_tab', 'smtp');
    }

    public function testConnection(Request $request, DatabaseSmtpMailService $mailService): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        try {
            $mailService->testConnection();
        } catch (\Throwable $exception) {
            return back()
                ->withErrors([
                    'smtp_test' => html_entity_decode('Ki&#7875;m tra k&#7871;t n&#7889;i SMTP th&#7845;t b&#7841;i: ').$exception->getMessage(),
                ])
                ->with('smtp_tab', 'smtp');
        }

        return back()
            ->with('status', html_entity_decode('K&#7871;t n&#7889;i SMTP th&#224;nh c&#244;ng.'))
            ->with('smtp_test_status', [
                'ok' => true,
                'message' => html_entity_decode('K&#7871;t n&#7889;i SMTP ho&#7841;t &#273;&#7897;ng b&#236;nh th&#432;&#7901;ng.'),
            ])
            ->with('smtp_tab', 'smtp');
    }

    public function sendTestEmail(
        Request $request,
        DatabaseSmtpMailService $mailService,
        OtpEmailTemplateService $templates
    ): RedirectResponse {
        $this->ensureSuperAdmin($request);

        $data = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $template = $templates->forgotPasswordTemplate();
        $preview = $templates->render($template, '123456', 5);

        try {
            $mailService->sendHtml($data['test_email'], $preview['subject'], $preview['html']);
        } catch (\Throwable $exception) {
            return back()
                ->withErrors([
                    'test_email' => html_entity_decode('G&#7917;i email ki&#7875;m tra th&#7845;t b&#7841;i: ').$exception->getMessage(),
                ])
                ->with('smtp_tab', 'smtp');
        }

        return back()
            ->with('status', html_entity_decode('&#272;&#227; g&#7917;i email ki&#7875;m tra th&#224;nh c&#244;ng.'))
            ->with('smtp_tab', 'smtp');
    }

    public function updateTemplate(Request $request, OtpEmailTemplateService $templates): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $errors = $templates->validateTemplateContent($data['subject'], $data['content']);

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'content' => $errors[0],
            ]);
        }

        $template = EmailTemplate::forgotPassword();
        $template->update([
            'subject' => trim($data['subject']),
            'content' => trim($data['content']),
        ]);

        return back()
            ->with('status', html_entity_decode('&#272;&#227; l&#432;u m&#7851;u email OTP.'))
            ->with('smtp_tab', 'template');
    }

    public function restoreTemplate(Request $request, OtpEmailTemplateService $templates): RedirectResponse
    {
        $this->ensureSuperAdmin($request);
        $templates->restoreForgotPasswordTemplate();

        return back()
            ->with('status', html_entity_decode('&#272;&#227; kh&#244;i ph&#7909;c m&#7851;u email m&#7863;c &#273;&#7883;nh.'))
            ->with('smtp_tab', 'template');
    }

    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless((bool) $request->user()?->is_admin, 403);
    }
}
