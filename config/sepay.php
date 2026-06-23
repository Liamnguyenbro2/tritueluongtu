<?php

return [
    'enabled' => env('SEPAY_ENABLED', true),
    'webhook_secret' => env('SEPAY_WEBHOOK_SECRET'),
    'webhook_verify_token' => env('SEPAY_WEBHOOK_VERIFY_TOKEN'),
];
