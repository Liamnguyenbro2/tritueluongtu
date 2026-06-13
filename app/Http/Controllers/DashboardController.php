<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonUnlock;
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
        $isAdmin = $user->isAdmin();
        $hasFullLibrarySubscriptionAccess = ! $isAdmin && $user->hasFullLibrarySubscriptionAccess();
        $requiresKycForPaidAccess = ! $isAdmin && $user->requiresKycForPaidAccess();
        $activeSubscription = $user->activeSubscription();
        $activeMonthlySubscription = $user->activeMonthlySubscription();
        $latestMonthlySubscription = $user->latestSubscription(config('quantum.plans.monthly_code'));
        $hasPerLessonMonthlyUnlocks = LessonUnlock::query()
            ->where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->exists();

        $trialExpiresAt = $user->trial_started_at?->copy()->addHours(config('quantum.trial_hours'));
        $trialActive = $trialExpiresAt && now()->lt($trialExpiresAt);

        $accessByLessonId = UserLessonAccess::query()
            ->where('user_id', $user->id)
            ->where('source', 'paid')
            ->get()
            ->keyBy('lesson_id');

        $unlockByLessonId = LessonUnlock::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('lesson_id');

        $lessons = Lesson::query()->orderBy('position')->get()->map(function (Lesson $lesson) use (
            $isAdmin,
            $hasFullLibrarySubscriptionAccess,
            $requiresKycForPaidAccess,
            $activeMonthlySubscription,
            $latestMonthlySubscription,
            $accessByLessonId,
            $unlockByLessonId,
            $trialActive,
            $trialExpiresAt,
        ) {
            $paidAccess = $accessByLessonId->get($lesson->id);
            $lessonUnlock = $unlockByLessonId->get($lesson->id);
            $paidAccessActive = $paidAccess?->isActive() ?? false;
            $lessonUnlockActive = $lessonUnlock?->isActive() ?? false;
            $hasLessonEntitlement = $isAdmin || $hasFullLibrarySubscriptionAccess || $lessonUnlockActive;
            $usesTrialFlow = $lesson->is_trial
                && $trialActive
                && ! $hasLessonEntitlement
                && ! $paidAccessActive;
            $isTrialActive = $usesTrialFlow && $trialActive;
            $isActive = $isAdmin ? true : ($usesTrialFlow ? $isTrialActive : $paidAccessActive);
            $expiresAt = $usesTrialFlow ? $trialExpiresAt : ($paidAccessActive ? $paidAccess->expires_at : null);
            $lessonMembershipExpiresAt = $lessonUnlockActive
                ? $lessonUnlock->expires_at
                : ($hasFullLibrarySubscriptionAccess ? $activeMonthlySubscription?->ends_at : null);
            $lessonMembershipExpired = $lessonUnlock !== null && ! $lessonUnlockActive;
            $legacyMonthlyExpired = ! $lessonMembershipExpired
                && ! $hasFullLibrarySubscriptionAccess
                && $latestMonthlySubscription?->grants_full_library
                && $latestMonthlySubscription?->ends_at
                && $latestMonthlySubscription->ends_at->lte(now());
            $canToggle = ! $isAdmin && ! $usesTrialFlow && ($hasLessonEntitlement || $paidAccessActive);
            $requiresKyc = $requiresKycForPaidAccess
                && ! $usesTrialFlow
                && ($hasLessonEntitlement || $paidAccessActive);
            $mediaUrl = $lesson->video_source_type === 'embed'
                ? route('lessons.player', $lesson)
                : ($lesson->media_path ? route('lessons.media', $lesson) : null);

            return [
                'id' => $lesson->id,
                'position' => $lesson->position,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'thumbnail_url' => $lesson->thumbnail_path ? route('lessons.thumbnail', $lesson) : null,
                'media_type' => $lesson->video_source_type === 'embed' ? 'embed-video' : $lesson->media_type,
                'media_url' => $requiresKyc ? null : $mediaUrl,
                'locked' => $requiresKyc ? true : ! $isActive,
                'trial' => $usesTrialFlow,
                'active' => $requiresKyc ? false : $isActive,
                'can_activate' => $requiresKyc ? false : $canToggle,
                'can_unlock' => false,
                'requires_membership_upgrade' => $requiresKyc ? false : (! $isAdmin && ! $usesTrialFlow && ! $canToggle),
                'requires_kyc' => $requiresKyc,
                'unlock_price_vnd' => (int) $lesson->unlock_price_vnd,
                'is_unlocked_lesson' => $lessonUnlockActive,
                'expires_at' => $expiresAt?->toIso8601String(),
                'expires_label' => $expiresAt?->format('d/m/Y H:i'),
                'membership_expires_at' => $lessonMembershipExpiresAt?->toIso8601String(),
                'membership_expires_label' => $lessonMembershipExpiresAt?->format('d/m/Y H:i'),
                'membership_expired' => $lessonMembershipExpired || $legacyMonthlyExpired,
            ];
        });

        [, $pendingAnnouncements] = $announcements->forUser($user);

        return view('dashboard', compact(
            'lessons',
            'trialExpiresAt',
            'activeSubscription',
            'activeMonthlySubscription',
            'hasPerLessonMonthlyUnlocks',
            'pendingAnnouncements',
            'requiresKycForPaidAccess'
        ));
    }

    public function toggle(Request $request, Lesson $lesson): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return back()->with('status', html_entity_decode('T&#224;i kho&#7843;n admin xem to&#224;n b&#7897; n&#7897;i dung m&#224; kh&#244;ng c&#7847;n b&#7853;t/t&#7855;t.'));
        }

        $existingAccess = UserLessonAccess::query()
            ->where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->where('source', 'paid')
            ->first();

        $hasLessonEntitlement = $user->hasFullLibrarySubscriptionAccess()
            || LessonUnlock::query()
                ->where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->where('expires_at', '>', now())
                ->exists();

        abort_unless($hasLessonEntitlement || $existingAccess?->isActive(), 403);

        if ($existingAccess?->isActive()) {
            $existingAccess->update([
                'revoked_at' => now(),
            ]);

            return back()->with('status', html_entity_decode('&#272;&#227; t&#7855;t n&#7897;i dung ').$lesson->title.'.');
        }

        UserLessonAccess::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'source' => 'paid',
                'starts_at' => now(),
                'expires_at' => now()->addDays(config('quantum.paid_lesson_active_days')),
                'revoked_at' => null,
            ]
        );

        return back()->with('status', html_entity_decode('&#272;&#227; b&#7853;t n&#7897;i dung ').$lesson->title.html_entity_decode(' trong 7 ng&#224;y.'));
    }

    public function unlock(Request $request, Lesson $lesson): RedirectResponse
    {
        return redirect()
            ->route('billing')
            ->with('status', html_entity_decode('H&#227;y v&#224;o m&#7909;c N&#226;ng c&#7845;p &#273;&#7875; mua g&#243;i th&#225;ng v&#224; ch&#7885;n b&#224;i h&#7885;c ').$lesson->title.'.');
    }
}
