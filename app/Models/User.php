<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'username',
        'name',
        'email',
        'phone',
        'password',
        'is_admin',
        'role',
        'trial_started_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'trial_started_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->is_admin || $this->role === 'admin';
    }

    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function bankAccount()
    {
        return $this->hasOne(BankAccount::class);
    }

    public function referralLink()
    {
        return $this->hasOne(ReferralLink::class);
    }

    public function referralsMade()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referredBy()
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function lessonAccesses()
    {
        return $this->hasMany(UserLessonAccess::class);
    }

    public function lessonUnlocks()
    {
        return $this->hasMany(LessonUnlock::class);
    }

    public function announcementReads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner')->where('type', 'user');
    }

    public function transactionLogs()
    {
        return $this->hasMany(TransactionLog::class);
    }

    public function activeSuspension()
    {
        return $this->hasOne(AccountSuspension::class)
            ->whereNull('revoked_at')
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->exists();
    }

    public function canActivatePaidLessons(): bool
    {
        return $this->hasActiveSubscription();
    }

    public function activeSubscription(?string $planCode = null): ?Subscription
    {
        return $this->activeSubscriptionsQuery($planCode)
            ->with('plan')
            ->orderByDesc('ends_at')
            ->first();
    }

    public function latestSubscription(?string $planCode = null): ?Subscription
    {
        return $this->subscriptions()
            ->with('plan')
            ->when($planCode, function (Builder $query, string $planCode) {
                $query->whereHas('plan', fn (Builder $planQuery) => $planQuery->where('code', $planCode));
            })
            ->orderByDesc('ends_at')
            ->first();
    }

    public function hasFullLibrarySubscriptionAccess(): bool
    {
        return $this->activeSubscriptionsQuery()
            ->where('grants_full_library', true)
            ->exists();
    }

    public function hasUnlockableMonthlyMembership(): bool
    {
        $subscription = $this->activeMonthlySubscription();

        return $subscription !== null && ! $subscription->grants_full_library;
    }

    public function activeMonthlySubscription(): ?Subscription
    {
        return $this->activeSubscription(config('quantum.plans.monthly_code'));
    }

    private function activeSubscriptionsQuery(?string $planCode = null)
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->when($planCode, function (Builder $query, string $planCode) {
                $query->whereHas('plan', fn (Builder $planQuery) => $planQuery->where('code', $planCode));
            });
    }
}
