<?php

namespace Tests\Feature;

use App\Jobs\ProcessSepayWebhookJob;
use App\Models\Lesson;
use App\Models\PaymentOrder;
use App\Models\Plan;
use App\Models\SepayWebhookLog;
use App\Models\User;
use App\Services\PaymentProcessor;
use App\Services\WalletLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SepayWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_sepay_webhook_endpoint_accepts_post_without_login_and_dispatches_job(): void
    {
        $this->seed();
        Bus::fake();

        $payload = [
            'transaction_id' => 'SEPAY-POST-001',
            'order_code' => 'ORDER-001',
            'amount' => 199000,
            'status' => 'success',
        ];

        $this->postJson('/api/payment/sepay/webhook', $payload)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('sepay_webhook_logs', [
            'status' => 'queued',
            'ip_address' => '127.0.0.1',
        ]);

        $log = SepayWebhookLog::query()->latest('id')->firstOrFail();
        $this->assertSame('SEPAY-POST-001', $log->payload['transaction_id'] ?? null);
        $this->assertIsArray($log->headers);

        Bus::assertDispatched(ProcessSepayWebhookJob::class);
    }

    public function test_sepay_webhook_requires_configured_token(): void
    {
        $this->seed();
        Bus::fake();
        Config::set('sepay.webhook_verify_token', 'sepay-test-token');

        $this->postJson('/api/payment/sepay/webhook', ['id' => 1001])
            ->assertForbidden();

        $this->withHeader('Authorization', 'Apikey sepay-test-token')
            ->postJson('/api/payment/sepay/webhook', ['id' => 1001])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('sepay_webhook_logs', 1);
        Bus::assertDispatched(ProcessSepayWebhookJob::class);
    }

    public function test_sepay_webhook_pays_yearly_order_and_ignores_duplicate_transaction(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'yearly')->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder($user->id, $plan->id, (int) $plan->price_vnd);
        $this->assertSame("TTLT-Y-{$order->id}", $order->code);

        $payload = [
            'id' => 64726631,
            'gateway' => 'MBBank',
            'content' => $order->code,
            'transferAmount' => (int) $order->amount_vnd,
        ];

        $this->postJson('/api/payment/sepay/webhook', $payload)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->postJson('/api/payment/sepay/webhook', $payload)->assertOk();

        $this->assertDatabaseHas('payment_transactions', [
            'gateway' => 'sepay',
            'gateway_transaction_id' => '64726631',
            'order_code' => $order->code,
            'amount' => (int) $order->amount_vnd,
            'status' => 'processed',
        ]);

        $this->assertDatabaseHas('sepay_webhook_logs', [
            'status' => 'processed',
        ]);
        $this->assertDatabaseHas('sepay_webhook_logs', [
            'status' => 'duplicate',
        ]);
        $this->assertDatabaseHas('payment_orders', [
            'id' => $order->id,
            'status' => 'paid',
            'provider_transaction_id' => '64726631',
        ]);
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_sepay_webhook_unlocks_selected_lesson(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder(
            $user->id,
            $plan->id,
            (int) $lesson->unlock_price_vnd,
            'bank_qr',
            [
                'selected_lesson_id' => $lesson->id,
                'selected_lesson_title' => $lesson->title,
                'selected_lesson_price_vnd' => (int) $lesson->unlock_price_vnd,
            ]
        );

        $this->postJson('/api/payment/sepay/webhook', [
            'id' => 64726632,
            'content' => 'Thanh toan '.$order->code,
            'transferAmount' => (int) $order->amount_vnd,
        ])->assertOk();

        $this->assertDatabaseHas('lesson_unlocks', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'amount_vnd' => (int) $lesson->unlock_price_vnd,
        ]);
        $this->assertSame(PaymentOrder::TYPE_COURSE, $order->fresh()->order_type);
        $this->assertSame("TTLT-C-{$order->id}", $order->code);
    }

    public function test_sepay_webhook_matches_order_code_when_bank_removes_hyphens_and_appends_reference(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder(
            $user->id,
            $plan->id,
            2000,
            'bank_qr',
            [
                'selected_lesson_id' => $lesson->id,
                'selected_lesson_title' => $lesson->title,
                'selected_lesson_price_vnd' => 2000,
            ]
        );

        $this->postJson('/api/payment/sepay/webhook', [
            'id' => 64751829,
            'content' => str_replace('-', '', $order->code).' FT26175031325390 kC5Y76TL/238972',
            'transferAmount' => 2000,
        ])->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertDatabaseHas('payment_transactions', [
            'gateway_transaction_id' => '64751829',
            'order_code' => $order->code,
            'status' => 'processed',
        ]);
        $this->assertDatabaseHas('sepay_webhook_logs', [
            'status' => 'processed',
        ]);
    }

    public function test_sepay_webhook_processes_after_response_without_a_queue_worker(): void
    {
        $this->seed();
        Config::set('queue.default', 'database');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder(
            $user->id,
            $plan->id,
            2000,
            'bank_qr',
            [
                'selected_lesson_id' => $lesson->id,
                'selected_lesson_title' => $lesson->title,
                'selected_lesson_price_vnd' => 2000,
            ]
        );

        $this->postJson('/api/payment/sepay/webhook', [
            'id' => 64757223,
            'content' => str_replace('-', '', $order->code).' FT26175505645050 kC5YEH6T/277844',
            'transferAmount' => 2000,
        ])->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertDatabaseHas('sepay_webhook_logs', [
            'status' => 'processed',
        ]);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_delayed_webhook_accepts_payment_made_before_order_expired(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder(
            $user->id,
            $plan->id,
            2000,
            'bank_qr',
            [
                'selected_lesson_id' => $lesson->id,
                'selected_lesson_title' => $lesson->title,
                'selected_lesson_price_vnd' => 2000,
            ]
        );
        $order->update(['expires_at' => now()->subMinute()]);
        $paidAt = now()->subMinutes(2)->startOfSecond();

        $this->actingAs($user)->get(route('billing'))->assertOk();
        $this->assertSame('cancelled', $order->fresh()->status);

        $this->postJson('/api/payment/sepay/webhook', [
            'id' => 64757224,
            'transactionDate' => $paidAt->format('Y-m-d H:i:s'),
            'content' => str_replace('-', '', $order->code).' FT26175505645051',
            'transferAmount' => 2000,
        ])->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertTrue($order->fresh()->paid_at->equalTo($paidAt));
        $this->assertDatabaseHas('payment_transactions', [
            'gateway_transaction_id' => '64757224',
            'status' => 'processed',
        ]);
    }

    public function test_sepay_webhook_credits_wallet_topup_once(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $payments = app(PaymentProcessor::class);
        $wallets = app(WalletLedgerService::class);
        $wallet = $wallets->walletForUser($user);
        $order = $payments->createWalletTopupOrder($user, 500000);
        $this->assertSame("TTLT-W-{$order->id}", $order->code);
        $payload = [
            'id' => 64726633,
            'content' => $order->code,
            'transferAmount' => 500000,
        ];

        $this->postJson('/api/payment/sepay/webhook', $payload)->assertOk();
        $this->postJson('/api/payment/sepay/webhook', $payload)->assertOk();

        $this->assertSame(500000, (int) $wallet->fresh()->balance_vnd);
        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallet->id,
            'amount_vnd' => 500000,
            'type' => 'sepay_wallet_topup',
        ]);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_sepay_webhook_rejects_amount_mismatch_without_granting_access(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'yearly')->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder($user->id, $plan->id, (int) $plan->price_vnd);

        $this->postJson('/api/payment/sepay/webhook', [
            'id' => 64726634,
            'content' => $order->code,
            'transferAmount' => (int) $order->amount_vnd - 1000,
        ])->assertOk();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertDatabaseHas('payment_transactions', [
            'gateway_transaction_id' => '64726634',
            'status' => 'amount_mismatch',
        ]);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_admin_can_view_sepay_webhook_logs_and_user_cannot(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        SepayWebhookLog::query()->create([
            'webhook_uuid' => '8dcb674f-4cc2-4dcb-96a9-0d7cf95cbca1',
            'headers' => ['x-sepay-token' => 'demo-token'],
            'payload' => ['transaction_id' => 'SEPAY-VIEW-001', 'amount' => 150000],
            'ip_address' => '10.10.10.10',
            'status' => 'queued',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.sepay-webhooks.index'))
            ->assertOk()
            ->assertSee('Nhật ký Webhook SePay')
            ->assertSee('SEPAY-VIEW-001')
            ->assertSee('10.10.10.10');

        $this->actingAs($user)
            ->get(route('admin.sepay-webhooks.index'))
            ->assertForbidden();
    }
}
