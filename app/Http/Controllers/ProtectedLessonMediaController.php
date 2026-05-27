<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\UserLessonAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    private function authorizeLessonMedia(Request $request, Lesson $lesson): void
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->is_admin) {
            return;
        }

        $trialExpiresAt = $user->trial_started_at?->copy()->addHours(config('quantum.trial_hours'));
        $trialAllowed = $lesson->is_trial && $trialExpiresAt && now()->lt($trialExpiresAt);
        $paidAllowed = ! $lesson->is_trial && UserLessonAccess::query()
            ->where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->where('source', 'paid')
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();

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
}
