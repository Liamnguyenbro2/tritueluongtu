<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SmtpSetting extends Model
{
    protected $fillable = [
        'gmail_address',
        'app_password_encrypted',
        'smtp_host',
        'smtp_port',
        'encryption',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'smtp_port' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->latest('id')->first();
    }

    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->latest('id')->first();
    }

    public function decryptedPassword(): ?string
    {
        if (! $this->app_password_encrypted) {
            return null;
        }

        return Crypt::decryptString($this->app_password_encrypted);
    }

    public function maskedPassword(): ?string
    {
        $password = $this->decryptedPassword();

        if (! $password) {
            return null;
        }

        if (strlen($password) <= 4) {
            return str_repeat('*', strlen($password));
        }

        return str_repeat('*', max(0, strlen($password) - 4)).substr($password, -4);
    }
}
