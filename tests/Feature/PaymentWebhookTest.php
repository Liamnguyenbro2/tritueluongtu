<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserLessonAccess;
use App\Services\PaymentProcessor;
use App\Services\WalletLedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_processes_valid_payment_once(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder($user->id, $plan->id, $plan->price_vnd);

        $payload = [
            'provider_transaction_id' => 'BANK-1',
            'amount' => $order->amount_vnd,
            'description' => $order->code,
            'paid_at' => now()->toIso8601String(),
        ];
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, 'test-secret');

        $this->withHeader('X-Bank-Signature', $signature)->postJson('/api/bank/webhook', $payload)->assertOk();
        $this->withHeader('X-Bank-Signature', $signature)->postJson('/api/bank/webhook', $payload)->assertOk();

        $this->assertDatabaseHas('payment_orders', ['id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_user_can_pay_plan_with_wallet_balance(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $wallet = app(WalletLedgerService::class)->walletForUser($user);
        app(WalletLedgerService::class)->credit($wallet, (int) $plan->price_vnd, 'test_topup');

        $this->actingAs($user)->post('/billing/orders', [
            'plan_id' => $plan->id,
            'payment_method' => 'wallet',
        ])->assertRedirect('/billing');

        $this->assertDatabaseHas('payment_orders', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'paid',
            'provider_transaction_id' => 'WALLET-'.\App\Models\PaymentOrder::query()->latest('id')->value('code'),
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallet->id,
            'amount_vnd' => -((int) $plan->price_vnd),
            'type' => 'wallet_payment',
        ]);
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_wallet_payment_requires_enough_balance(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();

        $this->actingAs($user)->from('/billing')->post('/billing/orders', [
            'plan_id' => $plan->id,
            'payment_method' => 'wallet',
        ])->assertRedirect('/billing')->assertSessionHasErrors('payment_method');

        $this->assertDatabaseMissing('payment_orders', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_paid_plan_time_is_extended_from_current_expiry(): void
    {
        $this->seed();
        Carbon::setTestNow('2026-05-25 10:00:00');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $monthly = Plan::query()->where('code', 'monthly')->firstOrFail();
        $yearly = Plan::query()->where('code', 'yearly')->firstOrFail();
        $payments = app(PaymentProcessor::class);

        $firstOrder = $payments->createOrder($user->id, $monthly->id, $monthly->price_vnd);
        $payments->complete($firstOrder, 'BANK-FIRST');

        Carbon::setTestNow('2026-06-01 10:00:00');
        $secondOrder = $payments->createOrder($user->id, $yearly->id, $yearly->price_vnd);
        $payments->complete($secondOrder, 'BANK-SECOND');

        $latestSubscription = Subscription::query()
            ->where('user_id', $user->id)
            ->orderByDesc('ends_at')
            ->firstOrFail();

        $this->assertSame('2027-06-24 10:00:00', $latestSubscription->ends_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_paid_lesson_active_cycle_is_seven_days_and_not_extended_while_active(): void
    {
        $this->seed();
        Carbon::setTestNow('2026-05-25 10:00:00');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->orderBy('position')->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder($user->id, $plan->id, $plan->price_vnd);
        app(PaymentProcessor::class)->complete($order, 'BANK-ACTIVE');

        $this->actingAs($user)->post(route('lessons.toggle', $lesson));

        $firstAccess = UserLessonAccess::query()
            ->where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->firstOrFail();

        $this->assertSame('2026-06-01 10:00:00', $firstAccess->expires_at->toDateTimeString());

        Carbon::setTestNow('2026-05-26 10:00:00');
        $this->actingAs($user)->post(route('lessons.toggle', $lesson));
        $this->assertSame('2026-06-01 10:00:00', $firstAccess->fresh()->expires_at->toDateTimeString());

        Carbon::setTestNow('2026-06-02 10:00:00');
        $this->actingAs($user)->post(route('lessons.toggle', $lesson));
        $this->assertSame('2026-06-09 10:00:00', $firstAccess->fresh()->expires_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_trial_lesson_uses_paid_activation_flow_after_plan_purchase(): void
    {
        $this->seed();
        Carbon::setTestNow('2026-05-25 10:00:00');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $user->update([
            'trial_started_at' => now()->subDays(10),
        ]);

        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $trialLesson = Lesson::query()->where('is_trial', true)->orderBy('position')->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder($user->id, $plan->id, $plan->price_vnd);
        app(PaymentProcessor::class)->complete($order, 'BANK-TRIAL-ACTIVE');

        $this->actingAs($user)
            ->post(route('lessons.toggle', $trialLesson))
            ->assertRedirect();

        $access = UserLessonAccess::query()
            ->where('user_id', $user->id)
            ->where('lesson_id', $trialLesson->id)
            ->firstOrFail();

        $this->assertSame('paid', $access->source);
        $this->assertSame('2026-06-01 10:00:00', $access->expires_at->toDateTimeString());

        Carbon::setTestNow();
    }
}
