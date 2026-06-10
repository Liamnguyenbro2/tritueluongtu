<?php

return [
    'withdrawal_min_vnd' => 100000,
    'trial_hours' => 48,
    'paid_lesson_active_days' => 7,
    'default_lesson_unlock_price_vnd' => 49000,
    'default_referral_code' => env('DEFAULT_REFERRAL_CODE', 'ADMIN'),
    'plans' => [
        'monthly_code' => 'monthly',
        'yearly_code' => 'yearly',
    ],
    'bank_webhook_secret' => env('BANK_WEBHOOK_SECRET'),
    'bank_qr' => [
        'bank_code' => env('BANK_QR_BANK_CODE'),
        'account_no' => env('BANK_QR_ACCOUNT_NO'),
        'account_name' => env('BANK_QR_ACCOUNT_NAME'),
    ],
    'media_embed' => [
        'allowed_hosts' => [
            'media.tritueluongtu.com',
        ],
        'frame_ancestors' => [
            "'self'",
            'https://tritueluongtu.com',
            'https://www.tritueluongtu.com',
            'http://127.0.0.1:8001',
            'http://localhost:8001',
        ],
    ],
    'allocation' => [
        'affiliate' => 30,
        'vat' => 10,
        'company_revenue' => 45,
        'shared_pool' => 15,
    ],
    'affiliate_commission_percent' => 30,
    'auth_sessions' => [
        'warning_seconds' => 300,
        'user' => [
            'idle_minutes' => 60 * 24,
            'absolute_minutes' => 60 * 24 * 7,
            'idle_label' => 'Không hoạt động trong 24 giờ',
            'absolute_label' => '7 ngày kể từ lúc đăng nhập',
        ],
        'privileged' => [
            'idle_minutes' => 60 * 4,
            'absolute_minutes' => 60 * 24,
            'idle_label' => 'Không hoạt động trong 4 giờ',
            'absolute_label' => '24 giờ kể từ lúc đăng nhập',
        ],
    ],
    'pool_share_groups' => [
        'A' => ['min' => 10, 'max' => 49, 'share_bp' => 3330],
        'B' => ['min' => 50, 'max' => 99, 'share_bp' => 3330],
        'C' => ['min' => 100, 'max' => null, 'share_bp' => 3340],
    ],
];
