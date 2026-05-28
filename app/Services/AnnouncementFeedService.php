<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;

class AnnouncementFeedService
{
    public function forUser(User $user): array
    {
        Announcement::fixedNotice();

        $readIds = AnnouncementRead::query()
            ->where('user_id', $user->id)
            ->pluck('announcement_id')
            ->all();
        $readIdLookup = array_flip($readIds);

        $rows = Announcement::query()
            ->visible()
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (Announcement $announcement) use ($readIdLookup) {
                $isRead = isset($readIdLookup[$announcement->id]);

                return [
                    'id' => $announcement->id,
                    'number' => '#'.str_pad((string) $announcement->id, 4, '0', STR_PAD_LEFT),
                    'type' => $announcement->type,
                    'type_label' => $announcement->type === Announcement::TYPE_FIXED ? 'Cố định' : 'Theo đợt',
                    'title' => $announcement->title,
                    'body' => $announcement->body,
                    'summary' => $announcement->summary(),
                    'image_url' => $announcement->image_url,
                    'created_at_label' => $announcement->created_at->format('d/m/Y | H:i'),
                    'date_label' => $announcement->created_at->format('d/m/Y'),
                    'is_read' => $isRead,
                    'status_label' => $isRead ? 'Đã đọc' : 'Chưa đọc',
                    'read_url' => route('announcements.read', $announcement),
                ];
            })
            ->values();

        $pending = $rows
            ->filter(fn (array $announcement) => ! $announcement['is_read'])
            ->sortBy(fn (array $announcement) => $announcement['type'] === Announcement::TYPE_FIXED ? 0 : 1)
            ->values();

        return [$rows, $pending];
    }
}
