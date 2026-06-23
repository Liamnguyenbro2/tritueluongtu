<?php

namespace Tests\Feature;

use App\Jobs\ProcessSepayWebhookJob;
use App\Models\PaymentTransaction;
use App\Models\SepayWebhookLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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

    public function test_sepay_webhook_job_records_payment_transaction(): void
    {
        $this->seed();

        $payload = [
            'transaction_id' => 'SEPAY-TX-001',
            'order_code' => 'ORDER-XYZ',
            'amount' => 49000,
            'transaction_type' => 'incoming_transfer',
            'status' => 'success',
        ];

        $this->postJson('/api/payment/sepay/webhook', $payload)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('payment_transactions', [
            'gateway' => 'sepay',
            'gateway_transaction_id' => 'SEPAY-TX-001',
            'order_code' => 'ORDER-XYZ',
            'amount' => 49000,
            'transaction_type' => 'incoming_transfer',
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('sepay_webhook_logs', [
            'status' => 'processed',
        ]);
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
