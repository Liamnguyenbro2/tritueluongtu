<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonUnlock extends Model
{
    protected $fillable = [
        'user_id',
        'lesson_id',
        'subscription_id',
        'amount_vnd',
        'unlocked_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_vnd' => 'integer',
            'unlocked_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isFuture();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
