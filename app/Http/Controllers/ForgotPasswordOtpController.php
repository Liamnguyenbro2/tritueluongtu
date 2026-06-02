<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DatabaseSmtpMailService;
use App\Services\ForgotPasswordOtpService;
use App\Services\OtpEmailTemplateService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForgotPasswordOtpController extends Controller
{
    private const STEP_EMAIL = 'email';
    private const STEP_OTP = 'otp';
    private const STEP_RESET = 'reset';
    private const STEP_DONE = 'done';

    private const SESSION_EMAIL = 'forgot_password.email';
    private const SESSION_VERIFIED = 'forgot_password.verified_email';
    private const SESSION_STEP = 'forgot_password.step';
    private const SESSION_RESEND_AVAILABLE_AT = 'forgot_password.resend_available_at';
    private const SESSION_COMPLETED = 'forgot_password.completed';

    public function show(Request $request): View
    {
        $email = (string) $request->session()->get(self::SESSION_EMAIL, '');
        $verifiedEmail = (string) $request->session()->get(self::SESSION_VERIFIED, '');
        $completed = (bool) $request->session()->get(self::SESSION_COMPLETED, false);
        $step = $completed
            ? self::STEP_DONE
            : ($verifiedEmail !== '' ? self::STEP_RESET : ($email !== '' ? self::STEP_OTP : self::STEP_EMAIL));
        $resendAvailableAt = $request->session()->get(self::SESSION_RESEND_AVAILABLE_AT);

        return view('auth.forgot-password', [
            'email' => $verifiedEmail !== '' ? $verifiedEmail : $email,
            'verifiedEmail' => $verifiedEmail,
            'otpExpireMinutes' => ForgotPasswordOtpService::EXPIRE_MINUTES,
            'step' => $step,
            'completed' => $completed,
            'resendAvailableAt' => $resendAvailableAt,
            'resendSeconds' => $resendAvailableAt
                ? max(0, now()->diffInSeconds(Carbon::parse($resendAvailableAt), false))
                : 0,
        ]);
    }

    public function sendOtp(
        Request $request,
        ForgotPasswordOtpService $otpService,
        OtpEmailTemplateService $templates,
        DatabaseSmtpMailService $mailService
    ): RedirectResponse {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Vui lòng nhập email tài khoản.',
            'email.email' => 'Email không đúng định dạng.',
        ]);

        $email = Str::lower(trim($data['email']));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'Email này chưa được đăng ký trong hệ thống.',
            ])->withInput();
        }

        $resendAvailableAt = $request->session()->get(self::SESSION_RESEND_AVAILABLE_AT);

        if (
            $request->session()->get(self::SESSION_EMAIL) === $email
            && $resendAvailableAt
            && now()->lt(Carbon::parse($resendAvailableAt))
        ) {
            return redirect()->route('password.forgot')->withErrors([
                'email' => 'Vui lòng chờ hết bộ đếm 60 giây trước khi gửi lại OTP.',
            ]);
        }

        [$record, $otp] = $otpService->issue($user, (string) $request->ip());
        $template = $templates->forgotPasswordTemplate();
        $payload = $templates->render($template, $otp, ForgotPasswordOtpService::EXPIRE_MINUTES);

        try {
            $mailService->sendHtml($email, $payload['subject'], $payload['html']);
        } catch (\Throwable $exception) {
            $record->update(['used_at' => now()]);

            return back()->withErrors([
                'email' => 'Không thể gửi OTP qua email: '.$exception->getMessage(),
            ])->withInput();
        }

        $request->session()->put(self::SESSION_EMAIL, $email);
        $request->session()->forget(self::SESSION_VERIFIED);
        $request->session()->put(self::SESSION_STEP, self::STEP_OTP);
        $request->session()->put(self::SESSION_RESEND_AVAILABLE_AT, now()->addSeconds(60)->toIso8601String());
        $request->session()->forget(self::SESSION_COMPLETED);

        return redirect()->route('password.forgot')
            ->with('status', 'Đã gửi OTP tới email của bạn. Vui lòng kiểm tra hộp thư.');
    }

    public function verifyOtp(Request $request, ForgotPasswordOtpService $otpService): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Vui lòng nhập mã OTP.',
            'otp.digits' => 'Mã OTP phải gồm đúng 6 số.',
        ]);

        $email = Str::lower(trim($data['email']));

        if ($request->session()->get(self::SESSION_EMAIL) !== $email) {
            return redirect()->route('password.forgot')->withErrors([
                'otp' => 'Phiên xác thực OTP không hợp lệ. Vui lòng yêu cầu mã mới.',
            ]);
        }

        $otpService->verify($email, $data['otp']);

        $request->session()->put(self::SESSION_VERIFIED, $email);
        $request->session()->put(self::SESSION_STEP, self::STEP_RESET);

        return redirect()->route('password.forgot')
            ->with('status', 'Xác thực OTP thành công. Bạn có thể đặt mật khẩu mới.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $verifiedEmail = Str::lower((string) $request->session()->get(self::SESSION_VERIFIED, ''));

        if ($verifiedEmail === '') {
            return redirect()->route('password.forgot')
                ->withErrors(['password' => 'Bạn chưa xác thực OTP. Vui lòng hoàn tất bước xác minh trước.']);
        }

        $data = $request->validate([
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(6)->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'password.confirmed' => 'Nhập lại mật khẩu không khớp.',
        ]);

        $user = User::query()->whereRaw('LOWER(email) = ?', [$verifiedEmail])->firstOrFail();
        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        $request->session()->forget([
            self::SESSION_EMAIL,
            self::SESSION_VERIFIED,
            self::SESSION_STEP,
            self::SESSION_RESEND_AVAILABLE_AT,
        ]);
        $request->session()->put(self::SESSION_COMPLETED, true);

        return redirect()->route('password.forgot')
            ->with('status', 'Đặt lại mật khẩu thành công. Bạn có thể đăng nhập ngay.');
    }
}
