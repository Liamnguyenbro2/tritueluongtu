<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = ['user_id', 'accepted_terms', 'accepted_terms_at'];

    protected function casts(): array
    {
        return ['accepted_terms' => 'boolean', 'accepted_terms_at' => 'datetime'];
    }
}

class BankAccount extends Model
{
    protected $fillable = ['user_id', 'bank_name', 'account_number', 'account_holder', 'can_edit'];

    protected function casts(): array
    {
        return ['can_edit' => 'boolean'];
    }
}

class ReferralLink extends Model
{
    protected $fillable = ['user_id', 'code'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class Referral extends Model
{
    protected $fillable = ['referrer_id', 'referred_id', 'activated_at'];

    protected function casts(): array
    {
        return ['activated_at' => 'datetime'];
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }
}

class Course extends Model
{
    protected $fillable = ['title', 'description'];

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}

class Lesson extends Model
{
    protected $fillable = [
        'course_id',
        'position',
        'title',
        'description',
        'thumbnail_path',
        'media_type',
        'media_path',
        'is_trial',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return ['is_trial' => 'boolean'];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}

class Plan extends Model
{
    protected $fillable = ['code', 'name', 'duration_days', 'price_vnd'];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'price_vnd' => 'integer',
        ];
    }
}

class Subscription extends Model
{
    protected $fillable = ['user_id', 'plan_id', 'starts_at', 'ends_at', 'status'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}

class PaymentOrder extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'code',
        'amount_vnd',
        'status',
        'provider_transaction_id',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'metadata' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}

class BankWebhookEvent extends Model
{
    protected $fillable = ['provider_transaction_id', 'status', 'payload', 'message'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}

class Wallet extends Model
{
    protected $fillable = ['owner_type', 'owner_id', 'type', 'balance_vnd'];

    public function owner()
    {
        return $this->morphTo();
    }

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}

class LedgerEntry extends Model
{
    protected $fillable = ['wallet_id', 'amount_vnd', 'direction', 'type', 'reference_type', 'reference_id', 'memo'];

    public function reference()
    {
        return $this->morphTo();
    }

    public function memoWithTimestamp(?string $memo = null): string
    {
        $text = trim((string) ($memo ?? $this->memo ?? $this->type));
        $timestamp = ($this->updated_at ?? $this->created_at ?? now())->format('d/m/Y | H:i');

        if ($text === '') {
            return $timestamp;
        }

        if (preg_match('/\d{2}\/\d{2}\/\d{4}\s\|\s\d{2}:\d{2}$/', $text)) {
            return $text;
        }

        return "{$text} - {$timestamp}";
    }
}

class WithdrawalRequest extends Model
{
    protected $fillable = ['user_id', 'bank_account_id', 'amount_vnd', 'status', 'admin_note', 'decided_at'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class AdminNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'body', 'read_at'];
}

class AccountSuspension extends Model
{
    protected $fillable = ['user_id', 'type', 'reason', 'starts_at', 'ends_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'revoked_at' => 'datetime'];
    }
}
