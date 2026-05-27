# Quantum Intelligence MVP

Laravel + MySQL MVP for a Vietnamese web platform with course access, payment orders, bank webhook confirmation, wallets, referral commission, withdrawals, and admin reporting.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8001
```

The current machine does not have PHP/Composer in PATH, so dependencies and migrations were not executed here.

## Seeded accounts

- Admin: `admin@example.com` / `password`
- User: `user@example.com` / `password`

## Webhook contract

POST `/api/bank/webhook` with header `X-Bank-Signature`.

Signature:

```php
hash_hmac('sha256', $rawJsonBody, env('BANK_WEBHOOK_SECRET'))
```

Payload:

```json
{
  "provider_transaction_id": "BANK-123",
  "amount": 199000,
  "description": "QIABC123",
  "paid_at": "2026-05-25T09:00:00+07:00"
}
```
