<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Announcement extends Model
{
    public const TYPE_FIXED = 'fixed';
    public const TYPE_CAMPAIGN = 'campaign';
    public const FIXED_SLUG = 'first-login-science-notice';

    protected $fillable = [
        'slug',
        'type',
        'title',
        'body',
        'image_url',
        'is_active',
        'created_by_user_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public static function fixedNotice(): self
    {
        return static::query()->firstOrCreate(
            ['slug' => self::FIXED_SLUG],
            [
                'type' => self::TYPE_FIXED,
                'title' => 'Thông báo trải nghiệm',
                'body' => 'Đây là chương trình có cơ sở nghiên cứu khoa học cải thiện tâm lý bằng việc thả lỏng, thiền định như giảm Stress giúp giấc ngủ ngon hơn…',
                'is_active' => true,
                'published_at' => now(),
            ]
        );
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereIn('type', [self::TYPE_FIXED, self::TYPE_CAMPAIGN]);
    }

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function readBy(User $user): AnnouncementRead
    {
        return $this->reads()->updateOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()]
        );
    }

    public function isReadBy(User $user): bool
    {
        if ($this->relationLoaded('reads')) {
            return $this->reads->contains('user_id', $user->id);
        }

        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function summary(int $limit = 110): string
    {
        $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($this->body)));

        return Str::limit($text, $limit);
    }
}
