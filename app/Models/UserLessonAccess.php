<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLessonAccess extends Model
{
    protected $table = 'user_lesson_access';

    protected $fillable = ['user_id', 'lesson_id', 'source', 'starts_at', 'expires_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function isActive(): bool
    {
        return is_null($this->revoked_at)
            && (! $this->starts_at || $this->starts_at->lte(now()))
            && (! $this->expires_at || $this->expires_at->gt(now()));
    }
}
