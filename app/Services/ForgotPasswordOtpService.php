<?php

namespace App\Services;

use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ForgotPasswordOtpService
{
    public const EXPIRE_MINUTES = 5;
    public const MAX_PER_EMAIL_WINDOW = 3;
    public const EMAIL_WINDOW_MINUTES = 15;
    public const MAX_PER_IP_WINDOW = 5;
    public const IP_WINDOW_HOURS = 24;

    public function issue(User $user, string $ipAddress): array
    {
        $this->ensureAllowed($user->email, $ipAddress);

        PasswordResetOtp::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($user->email)])
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $record = PasswordResetOtp::query()->create([
            'user_id' => $user->id,
            'email' => strtolower($user->email),
            'otp_hash' => Hash::make($otp),
            'ip_address' => $ipAddress,
            'expires_at' => now()->addMinutes(self::EXPIRE_MINUTES),
        ]);

        return [$record, $otp];
    }

    public function verify(string $email, string $otp): PasswordResetOtp
    {
        $record = PasswordResetOtp::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'otp' => 'Mã OTP không hợp lệ hoặc đã hết hiệu lực.',
            ]);
        }

        if ($record->isExpired()) {
            $record->update(['used_at' => now()]);

            throw ValidationException::withMessages([
                'otp' => 'Mã OTP đã hết hiệu lực. Vui lòng yêu cầu mã mới.',
            ]);
        }

        if (! Hash::check(trim($otp), $record->otp_hash)) {
            throw ValidationException::withMessages([
                'otp' => 'Mã OTP không chính xác.',
            ]);
        }

        $record->update(['used_at' => now()]);

        return $record->fresh();
    }

    private function ensureAllowed(string $email, string $ipAddress): void
    {
        $emailCount = PasswordResetOtp::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->where('created_at', '>=', now()->subMinutes(self::EMAIL_WINDOW_MINUTES))
            ->count();

        if ($emailCount >= self::MAX_PER_EMAIL_WINDOW) {
            throw ValidationException::withMessages([
                'email' => 'Email này đã yêu cầu OTP quá nhiều lần trong 15 phút gần đây.',
            ]);
        }

        $ipCount = PasswordResetOtp::query()
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subHours(self::IP_WINDOW_HOURS))
            ->count();

        if ($ipCount >= self::MAX_PER_IP_WINDOW) {
            throw ValidationException::withMessages([
                'email' => 'Thiết bị hoặc IP này đã vượt quá giới hạn gửi OTP trong 24 giờ.',
            ]);
        }
    }
}
