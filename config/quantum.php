<?php

return [
    'withdrawal_min_vnd' => 100000,
    'trial_hours' => 48,
    'paid_lesson_active_days' => 7,
    'default_referral_code' => env('DEFAULT_REFERRAL_CODE', 'ADMIN'),
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
    'pool_share_groups' => [
        'A' => ['min' => 10, 'max' => 49, 'share_bp' => 3330],
        'B' => ['min' => 50, 'max' => 99, 'share_bp' => 3330],
        'C' => ['min' => 100, 'max' => null, 'share_bp' => 3340],
    ],
];
