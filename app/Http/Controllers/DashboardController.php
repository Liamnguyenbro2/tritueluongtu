<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Subscription;
use App\Models\UserLessonAccess;
use App\Services\AnnouncementFeedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, AnnouncementFeedService $announcements): View
    {
        $user = $request->user();

        $trialExpiresAt = $user->trial_started_at?->copy()->addHours(config('quantum.trial_hours'));
        $trialActive = $trialExpiresAt && now()->lt($trialExpiresAt);
        $canActivatePaidLessons = $user->canActivatePaidLessons();
        $activeSubscription = Subscription::query()
            ->with('plan')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->orderByDesc('ends_at')
            ->first();

        $accessByLessonId = UserLessonAccess::query()
            ->where('user_id', $user->id)
            ->where('source', 'paid')
            ->whereNull('revoked_at')
            ->get()
            ->keyBy('lesson_id');

        $lessons = Lesson::query()->orderBy('position')->get()->map(function (Lesson $lesson) use ($accessByLessonId, $canActivatePaidLessons, $trialActive, $trialExpiresAt) {
            $paidAccess = $accessByLessonId->get($lesson->id);
            $paidAccessActive = $paidAccess?->isActive() ?? false;
            $usesPaidFlow = $canActivatePaidLessons || $paidAccessActive;
            $usesTrialFlow = $lesson->is_trial && ! $usesPaidFlow;
            $isTrialActive = $usesTrialFlow && $trialActive;
            $isUnlocked = $usesTrialFlow ? $isTrialActive : $paidAccessActive;
            $expiresAt = $usesTrialFlow ? $trialExpiresAt : ($paidAccessActive ? $paidAccess->expires_at : null);

            return [
                'id' => $lesson->id,
                'position' => $lesson->position,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'thumbnail_url' => $lesson->thumbnail_path ? route('lessons.thumbnail', $lesson) : null,
                'media_type' => $lesson->media_type,
                'media_url' => $lesson->media_path ? route('lessons.media', $lesson) : null,
                'locked' => ! $isUnlocked,
                'trial' => $usesTrialFlow,
                'active' => $isUnlocked,
                'can_activate' => ! $usesTrialFlow && $canActivatePaidLessons,
                'expires_at' => $expiresAt?->toIso8601String(),
                'expires_label' => $expiresAt?->format('d/m/Y H:i'),
            ];
        });

        [, $pendingAnnouncements] = $announcements->forUser($user);

        return view('dashboard', compact(
            'lessons',
            'trialExpiresAt',
            'activeSubscription',
            'pendingAnnouncements'
        ));
    }

    public function toggle(Request $request, Lesson $lesson): RedirectResponse
    {
        abort_unless($request->user()->canActivatePaidLessons(), 403);

        $existingAccess = UserLessonAccess::query()
            ->where('user_id', $request->user()->id)
            ->where('lesson_id', $lesson->id)
            ->where('source', 'paid')
            ->whereNull('revoked_at')
            ->first();

        if ($existingAccess?->isActive()) {
            return back()->with('status', "Nội dung {$lesson->title} đang Active.");
        }

        UserLessonAccess::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'source' => 'paid',
                'starts_at' => now(),
                'expires_at' => now()->addDays(config('quantum.paid_lesson_active_days')),
                'revoked_at' => null,
            ]
        );

        return back()->with('status', "Đã kích hoạt nội dung {$lesson->title} trong 7 ngày.");
    }
}
