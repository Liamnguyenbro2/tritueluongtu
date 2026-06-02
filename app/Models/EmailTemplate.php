<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    public const FORGOT_PASSWORD = 'forgot_password';

    public const DEFAULT_SUBJECT = 'Khôi phục mật khẩu - {{site_name}}';

    public const DEFAULT_CONTENT = <<<TEXT
Xin chào,

Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.
Mã OTP của bạn là:
{{otp}}

Mã có hiệu lực trong {{expire_minutes}} phút.
Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.

Trân trọng,
{{site_name}}
TEXT;

    protected $fillable = [
        'template_key',
        'subject',
        'content',
    ];

    public static function forgotPassword(): self
    {
        return static::query()->firstOrCreate(
            ['template_key' => self::FORGOT_PASSWORD],
            [
                'subject' => self::DEFAULT_SUBJECT,
                'content' => self::DEFAULT_CONTENT,
            ]
        );
    }
}
