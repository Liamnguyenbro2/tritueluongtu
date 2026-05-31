<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function branding(): array
    {
        return [
            'logo_url' => static::getValue('brand_logo_url'),
            'eyebrow' => static::getValue('brand_eyebrow', 'Quantum SaaS'),
            'name' => static::getValue('brand_name', 'Năng Lượng'),
        ];
    }

    public static function headerMarqueeText(): string
    {
        return static::getValue(
            'header_marquee_text',
            'He thong su dung trai nghiem hinh anh va am thanh mo phong trang thai: Alpha, Theta, Deep Relaxation, Focus State.'
        ) ?? '';
    }
}
