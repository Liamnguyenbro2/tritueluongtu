<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
}
