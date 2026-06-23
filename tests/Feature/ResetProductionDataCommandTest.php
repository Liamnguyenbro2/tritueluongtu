<?php

namespace Tests\Feature;

use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\ReferralLink;
use App\Models\SepayWebhookLog;
use App\Models\Subscription;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Models\BankAccount;
use App\Services\WalletLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetProductionDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_command_removes_customers_and_financial_data_but_keeps_admin_and_accountant(): void
    {
        $this->seed();

        $customer = User::query()->create([
            'username' => 'customer01',
            'name' => 'Customer 01',
            'email' => 'customer01@example.com',
            'phone' => '0999000001',
            'password' => 'password',
            'role' => 'user',
            'trial_started_at' => now(),
        ]);

        $customer->profile()->create([
            'accepted_terms' => true,
            'accepted_terms_at' => now(),
        ]);

        $plan = Plan::query()->firstOrFail();
        $wallets = app(WalletLedgerService::class);
        $wallet = $wallets->walletForUser($customer);

        ReferralLink::query()->create([
            'user_id' => $customer->id,
            'code' => 'CUSTOMER01',
        ]);

        PaymentOrder::query()->create([
            'user_id' => $customer->id,
            'plan_id' => $plan->id,
            'code' => 'TXN-RESET-001',
            'amount_vnd' => 199000,
            'status' => 'paid',
            'provider_transaction_id' => 'BANK-RESET-001',
            'paid_at' => now(),
        ]);

        Subscription::query()->create([
            'user_id' => $customer->id,
            'plan_id' => $plan->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'status' => 'active',
        ]);

        $bankAccount = BankAccount::query()->create([
            'user_id' => $customer->id,
            'bank_name' => 'Vietcombank',
            'account_number' => '0123456789',
            'account_holder' => 'Customer 01',
            'can_edit' => true,
        ]);

        WithdrawalRequest::query()->create([
            'user_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'amount_vnd' => 50000,
            'status' => 'pending',
        ]);

        $wallets->credit($wallet, 300000, 'admin_transfer_in');

        TransactionLog::query()->create([
            'user_id' => $customer->id,
            'transaction_type' => TransactionLog::TYPE_MONEY_IN,
            'amount' => 300000,
            'description' => 'Test reset',
            'status' => TransactionLog::STATUS_SUCCESS,
            'reference_id' => 'TEST-RESET-1',
        ]);

        SepayWebhookLog::query()->create([
            'webhook_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'headers' => ['x-test' => '1'],
            'payload' => ['transaction_id' => 'SP-RESET-1'],
            'ip_address' => '127.0.0.1',
            'status' => 'processed',
        ]);

        PaymentTransaction::query()->create([
            'gateway' => 'sepay',
            'gateway_transaction_id' => 'SP-RESET-1',
            'order_code' => 'TXN-RESET-001',
            'amount' => 199000,
            'transaction_type' => 'bank_transfer',
            'status' => 'received',
            'raw_payload' => ['transaction_id' => 'SP-RESET-1'],
            'processed_at' => now(),
        ]);

        $this->artisan('system:reset-production-data --force')
            ->assertSuccessful()
            ->expectsOutput('Production data reset completed.');

        $this->assertDatabaseMissing('users', ['email' => 'customer01@example.com']);
        $this->assertDatabaseCount('payment_orders', 0);
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseCount('ledger_entries', 0);
        $this->assertDatabaseCount('transaction_logs', 0);
        $this->assertDatabaseCount('withdrawal_requests', 0);
        $this->assertDatabaseCount('sepay_webhook_logs', 0);
        $this->assertDatabaseCount('payment_transactions', 0);
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'accountant@example.com']);
        $this->assertDatabaseHas('wallets', ['owner_type' => null, 'owner_id' => null, 'type' => 'admin']);
        $this->assertDatabaseHas('wallets', ['owner_type' => null, 'owner_id' => null, 'type' => 'tax']);
        $this->assertDatabaseHas('wallets', ['owner_type' => null, 'owner_id' => null, 'type' => 'shared_pool']);
    }
}
