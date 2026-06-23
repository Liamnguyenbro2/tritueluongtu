<?php

return [
    'enabled' => env('SEPAY_ENABLED', true),
    'webhook_secret' => env('SEPAY_WEBHOOK_SECRET'),
    'webhook_verify_token' => env('SEPAY_WEBHOOK_VERIFY_TOKEN'),
    'order_expire_minutes' => (int) env('SEPAY_ORDER_EXPIRE_MINUTES', 30),
    'wallet_topup_min_vnd' => (int) env('SEPAY_WALLET_TOPUP_MIN_VND', 10000),
    'wallet_topup_max_vnd' => (int) env('SEPAY_WALLET_TOPUP_MAX_VND', 500000000),
    'vietqr_image_base_url' => env('SEPAY_VIETQR_IMAGE_BASE_URL', 'https://img.vietqr.io/image'),
];
