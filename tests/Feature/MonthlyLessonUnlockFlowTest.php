<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\LessonUnlock;
use App\Models\PaymentOrder;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserLessonAccess;
use App\Services\WalletLedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyLessonUnlockFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_monthly_user_keeps_full_library_toggle_until_expiry(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->orderBy('position')->firstOrFail();

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(29),
            'status' => 'active',
            'grants_full_library' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('lessons', function ($lessons) use ($lesson) {
                $item = $lessons->firstWhere('id', $lesson->id);

                return $item !== null
                    && $item['can_activate'] === true
                    && $item['requires_membership_upgrade'] === false
                    && $item['membership_expires_at'] !== null;
            });
    }

    public function test_expired_legacy_monthly_user_falls_back_to_upgrade_flow(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->orderBy('position')->firstOrFail();

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now()->subDays(31),
            'ends_at' => now()->subMinute(),
            'status' => 'active',
            'grants_full_library' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('lessons', function ($lessons) use ($lesson) {
                $item = $lessons->firstWhere('id', $lesson->id);

                return $item !== null
                    && $item['can_activate'] === false
                    && $item['requires_membership_upgrade'] === true
                    && $item['membership_expired'] === true
                    && $item['membership_expires_at'] === null;
            });
    }

    public function test_monthly_checkout_unlocks_only_selected_lesson_with_its_own_30_day_timer(): void
    {
        $this->seed();
        Carbon::setTestNow('2026-06-05 10:00:00');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $lessons = Lesson::query()->where('is_trial', false)->orderBy('position')->take(2)->get();
        $lessonA = $lessons[0];
        $lessonB = $lessons[1];
        $wallet = app(WalletLedgerService::class)->walletForUser($user);

        app(WalletLedgerService::class)->credit($wallet, (int) $plan->price_vnd, 'test_topup');

        $this->actingAs($user)
            ->post(route('billing.orders.store'), [
                'plan_id' => $plan->id,
                'lesson_id' => $lessonA->id,
                'payment_method' => 'wallet',
            ])
            ->assertRedirect(route('billing'));

        $subscription = Subscription::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
        $unlock = LessonUnlock::query()->where('user_id', $user->id)->where('lesson_id', $lessonA->id)->firstOrFail();

        $this->assertSame($subscription->ends_at->toDateTimeString(), $unlock->expires_at?->toDateTimeString());

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('lessons', function ($lessons) use ($lessonA, $lessonB, $subscription) {
                $itemA = $lessons->firstWhere('id', $lessonA->id);
                $itemB = $lessons->firstWhere('id', $lessonB->id);

                return $itemA !== null
                    && $itemB !== null
                    && $itemA['membership_expires_at'] === $subscription->ends_at->toIso8601String()
                    && $itemA['requires_membership_upgrade'] === false
                    && $itemA['can_activate'] === true
                    && $itemB['membership_expires_at'] === null
                    && $itemB['requires_membership_upgrade'] === true
                    && $itemB['can_activate'] === false;
            });

        Carbon::setTestNow();
    }

    public function test_expired_monthly_lesson_unlock_requires_upgrade_again(): void
    {
        $this->seed();
        Carbon::setTestNow('2026-06-05 10:00:00');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->orderBy('position')->firstOrFail();

        $subscription = Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->subDays(10),
            'status' => 'active',
            'grants_full_library' => false,
        ]);

        LessonUnlock::query()->create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'subscription_id' => $subscription->id,
            'amount_vnd' => 0,
            'unlocked_at' => now()->subDays(40),
            'expires_at' => now()->subDays(10),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('lessons', function ($lessons) use ($lesson) {
                $item = $lessons->firstWhere('id', $lesson->id);

                return $item !== null
                    && $item['membership_expires_at'] === null
                    && $item['membership_expired'] === true
                    && $item['requires_membership_upgrade'] === true
                    && $item['can_activate'] === false;
            });

        Carbon::setTestNow();
    }

    public function test_user_can_repurchase_same_lesson_after_monthly_timer_expires(): void
    {
        $this->seed();
        Carbon::setTestNow('2026-06-05 10:00:00');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->orderBy('position')->firstOrFail();
        $wallet = app(WalletLedgerService::class)->walletForUser($user);

        $expiredSubscription = Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now()->subDays(50),
            'ends_at' => now()->subDays(20),
            'status' => 'active',
            'grants_full_library' => false,
        ]);

        LessonUnlock::query()->create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'subscription_id' => $expiredSubscription->id,
            'amount_vnd' => 0,
            'unlocked_at' => now()->subDays(50),
            'expires_at' => now()->subDays(20),
        ]);

        app(WalletLedgerService::class)->credit($wallet, (int) $plan->price_vnd, 'test_topup');

        $this->actingAs($user)
            ->post(route('billing.orders.store'), [
                'plan_id' => $plan->id,
                'lesson_id' => $lesson->id,
                'payment_method' => 'wallet',
            ])
            ->assertRedirect(route('billing'));

        $subscription = Subscription::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
        $unlock = LessonUnlock::query()->where('user_id', $user->id)->where('lesson_id', $lesson->id)->firstOrFail();

        $this->assertSame($subscription->id, $unlock->subscription_id);
        $this->assertSame($subscription->ends_at->toDateTimeString(), $unlock->expires_at?->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_activated_lesson_keeps_existing_7_day_toggle_cycle(): void
    {
        $this->seed();
        Carbon::setTestNow('2026-06-05 10:00:00');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->orderBy('position')->firstOrFail();

        $subscription = Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(29),
            'status' => 'active',
            'grants_full_library' => false,
        ]);

        LessonUnlock::query()->create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'subscription_id' => $subscription->id,
            'amount_vnd' => 0,
            'unlocked_at' => now()->subDay(),
            'expires_at' => $subscription->ends_at,
        ]);

        $this->actingAs($user)->post(route('lessons.toggle', $lesson))->assertRedirect();

        $access = UserLessonAccess::query()
            ->where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->firstOrFail();

        $this->assertSame('2026-06-12 10:00:00', $access->expires_at->toDateTimeString());

        Carbon::setTestNow('2026-06-06 10:00:00');
        $this->actingAs($user)->post(route('lessons.toggle', $lesson))->assertRedirect();
        $this->assertNotNull($access->fresh()->revoked_at);

        Carbon::setTestNow('2026-06-07 10:00:00');
        $this->actingAs($user)->post(route('lessons.toggle', $lesson))->assertRedirect();
        $this->assertSame('2026-06-14 10:00:00', $access->fresh()->expires_at->toDateTimeString());

        Carbon::setTestNow();
    }
}
