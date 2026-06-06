<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'accepted_terms',
        'accepted_terms_at',
        'voice_sample_path',
        'voice_sample_uploaded_at',
        'voice_sample_delete_after_at',
        'voice_sample_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_terms' => 'boolean',
            'accepted_terms_at' => 'datetime',
            'voice_sample_uploaded_at' => 'datetime',
            'voice_sample_delete_after_at' => 'datetime',
            'voice_sample_completed_at' => 'datetime',
        ];
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
        'video_source_type',
        'embed_url',
        'is_trial',
        'duration_minutes',
        'unlock_price_vnd',
    ];

    protected function casts(): array
    {
        return [
            'is_trial' => 'boolean',
            'unlock_price_vnd' => 'integer',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}

class Plan extends Model
{
    protected $fillable = ['code', 'name', 'description', 'duration_days', 'price_vnd', 'features', 'bank_qr_enabled', 'bank_qr_image_path', 'wallet_enabled'];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'price_vnd' => 'integer',
            'features' => 'array',
            'bank_qr_enabled' => 'boolean',
            'wallet_enabled' => 'boolean',
        ];
    }

    public function billingFeatures(): array
    {
        $features = is_array($this->features) ? array_values(array_filter($this->features)) : [];

        return $features ?: [
            'Mở quyền kích hoạt các khóa trả phí',
            'Active từng khóa trong 7 ngày khi cần học',
            'Ghi nhận đầy đủ trong lịch sử hóa đơn',
        ];
    }

    public function paymentOrders()
    {
        return $this->hasMany(PaymentOrder::class);
    }

    public function allowsPaymentMethod(string $method): bool
    {
        return match ($method) {
            'bank_qr' => $this->bank_qr_enabled,
            'wallet' => $this->wallet_enabled,
            default => false,
        };
    }

    public function bankQrImageUrl(): ?string
    {
        if (! $this->bank_qr_image_path) {
            return null;
        }

        return route('plans.qr-image', $this);
    }

    public function bankQrImageDownloadUrl(): ?string
    {
        if (! $this->bank_qr_image_path) {
            return null;
        }

        return route('plans.qr-image', [$this, 'download' => 1]);
    }

    public function bankQrImageFileName(): ?string
    {
        if (! $this->bank_qr_image_path) {
            return null;
        }

        $extension = pathinfo($this->bank_qr_image_path, PATHINFO_EXTENSION) ?: 'png';

        return sprintf('plan-qr-%s.%s', $this->code ?: $this->id, $extension);
    }
}

class Subscription extends Model
{
    protected $fillable = ['user_id', 'plan_id', 'starts_at', 'ends_at', 'status', 'grants_full_library'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'grants_full_library' => 'boolean',
        ];
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
    protected $fillable = ['owner_type', 'owner_id', 'type', 'balance_vnd', 'is_locked'];

    protected function casts(): array
    {
        return ['is_locked' => 'boolean'];
    }

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

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

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

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
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
