<?php

namespace App\Services;

use Illuminate\Http\Request;

class BankWebhookVerifier
{
    public function valid(Request $request): bool
    {
        $secret = (string) config('quantum.bank_webhook_secret');

        if ($secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, (string) $request->header('X-Bank-Signature'));
    }
}
