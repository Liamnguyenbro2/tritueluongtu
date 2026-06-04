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

- Admin: `......com` / `pass.....`
- User: `.......com` / `pass....`

## SMTP Gmail and forgot-password OTP

The admin area now includes a dedicated SMTP + OTP email module for password recovery.

### Admin menu

- Open `Admin Console -> Cấu hình Email OTP`
- Only the super admin account (`is_admin = true`) can access and edit this page

### SMTP fields

- Gmail Address
- App Password
- SMTP Host
- SMTP Port
- Encryption

The SMTP config is stored in the `smtp_settings` table. The Gmail App Password is encrypted with Laravel `Crypt::encryptString()`.

### Runtime behavior

- The app does not write to `.env`
- When sending OTP email, Laravel loads SMTP settings from database at runtime with `Config::set()`
- The config is only used for forgot-password OTP and SMTP test email

### Email OTP template

The template is stored in `email_templates` with:

- `template_key = forgot_password`
- editable `subject`
- editable `content`

Supported variables:

- `{{otp}}`
- `{{expire_minutes}}`
- `{{site_name}}`
- `{{current_year}}`

Required variables:

- `{{otp}}`
- `{{expire_minutes}}`

### Forgot-password flow

1. Open `/login`
2. Click `Quên mật khẩu?`
3. Enter the account email
4. Receive the 6-digit OTP by email
5. Verify OTP
6. Set a new password

Rules:

- OTP expires after 5 minutes
- OTP is one-time only
- Max 3 OTP requests per 15 minutes for each email
- Max 5 OTP requests per 24 hours for each IP

### Deploy notes

This feature adds new migrations:

- `smtp_settings`
- `email_templates`
- `password_reset_otps`

Run:

```bash
php artisan migrate --force
php artisan optimize:clear
```

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
