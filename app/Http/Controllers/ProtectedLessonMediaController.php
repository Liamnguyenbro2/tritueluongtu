<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonUnlock;
use App\Models\UserLessonAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProtectedLessonMediaController extends Controller
{
    public function thumbnail(Request $request, Lesson $lesson): StreamedResponse
    {
        abort_unless($request->user(), 403);

        abort_unless($lesson->thumbnail_path, 404);

        return $this->stream($lesson->thumbnail_path);
    }

    public function media(Request $request, Lesson $lesson): StreamedResponse
    {
        $this->authorizeLessonMedia($request, $lesson);

        abort_unless($lesson->media_path, 404);

        return $this->stream($lesson->media_path);
    }

    public function player(Request $request, Lesson $lesson): Response
    {
        $this->authorizeLessonMedia($request, $lesson);

        abort_unless($this->isEmbedLesson($lesson), 404);

        $playbackUrl = $this->resolveEmbedPlaybackUrl($lesson);
        abort_unless($playbackUrl, 404);

        $frameAncestors = implode(' ', config('quantum.media_embed.frame_ancestors', ["'self'"]));
        $mode = $this->isIframeEmbedUrl($playbackUrl) ? 'iframe' : 'video';

        return response()
            ->view('lessons.player', [
                'lesson' => $lesson,
                'playbackUrl' => $playbackUrl,
                'mode' => $mode,
            ])
            ->header('Content-Security-Policy', "frame-ancestors {$frameAncestors};")
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('Cache-Control', 'no-store, private');
    }

    private function authorizeLessonMedia(Request $request, Lesson $lesson): void
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->isAdmin()) {
            return;
        }

        $trialExpiresAt = $user->trial_started_at?->copy()->addHours(config('quantum.trial_hours'));
        $paidAllowed = UserLessonAccess::query()
            ->where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->where('source', 'paid')
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
        $hasLessonEntitlement = $user->hasFullLibrarySubscriptionAccess()
            || LessonUnlock::query()
                ->where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->where('expires_at', '>', now())
                ->exists();
        $trialAllowed = $lesson->is_trial
            && ! $hasLessonEntitlement
            && ! $paidAllowed
            && $trialExpiresAt
            && now()->lt($trialExpiresAt);

        abort_unless($trialAllowed || $paidAllowed, 403);
    }

    private function stream(string $path): StreamedResponse
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($path) && Storage::disk('public')->exists($path)) {
            $disk = Storage::disk('public');
        }

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, noarchive, nosnippet',
        ]);
    }

    private function isEmbedLesson(Lesson $lesson): bool
    {
        return $lesson->video_source_type === 'embed' && filled($lesson->embed_url);
    }

    private function resolveEmbedPlaybackUrl(Lesson $lesson): ?string
    {
        $url = trim((string) $lesson->embed_url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        $host = Str::lower((string) ($parts['host'] ?? ''));

        if (! in_array($host, config('quantum.media_embed.allowed_hosts', []), true)) {
            return null;
        }

        return $url;
    }

    private function isIframeEmbedUrl(string $url): bool
    {
        $path = Str::lower((string) parse_url($url, PHP_URL_PATH));

        return ! Str::endsWith($path, ['.mp4', '.webm', '.ogg', '.m4v', '.mov']);
    }
}
