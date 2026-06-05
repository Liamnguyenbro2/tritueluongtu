<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\TransactionLog;
use App\Models\User;
use App\Services\PaymentProcessor;
use App\Services\WalletLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_and_reward_flows_write_transaction_logs_for_user(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'yearly')->firstOrFail();
        $wallets = app(WalletLedgerService::class);
        $wallet = $wallets->walletForUser($user);

        $wallets->credit($wallet, (int) $plan->price_vnd, 'test_topup', null, 'Nạp tiền test vào ví.');

        $order = app(PaymentProcessor::class)->payWithWallet($user, $plan, (int) $plan->price_vnd);

        $wallets->credit($wallet, 450000, 'referral_commission', $order, 'Hoa hồng affiliate từ user #25 - abc@gmail.com kích hoạt gói.');
        $wallets->credit($wallet, 148500, 'pool_share_payout', null, 'Chi lại Pool Share ngày 30/05/2026.');

        $this->assertDatabaseHas('transaction_logs', [
            'user_id' => $user->id,
            'transaction_type' => TransactionLog::TYPE_PLAN_UPGRADE,
            'amount' => -((int) $plan->price_vnd),
            'status' => TransactionLog::STATUS_SUCCESS,
            'reference_id' => $order->code,
        ]);

        $this->assertDatabaseHas('transaction_logs', [
            'user_id' => $user->id,
            'transaction_type' => TransactionLog::TYPE_AFFILIATE,
            'amount' => 450000,
            'status' => TransactionLog::STATUS_SUCCESS,
        ]);

        $this->assertDatabaseHas('transaction_logs', [
            'user_id' => $user->id,
            'transaction_type' => TransactionLog::TYPE_POOL_SHARE,
            'amount' => 148500,
            'status' => TransactionLog::STATUS_SUCCESS,
        ]);
    }

    public function test_user_can_view_transaction_history_page_with_filters(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        TransactionLog::query()->create([
            'user_id' => $user->id,
            'transaction_type' => TransactionLog::TYPE_AFFILIATE,
            'amount' => 450000,
            'description' => 'Hoa hồng affiliate từ user #25 - abc@gmail.com kích hoạt gói.',
            'notes' => 'Mã: LE-1',
            'status' => TransactionLog::STATUS_SUCCESS,
            'reference_id' => 'LE-1',
        ]);

        TransactionLog::query()->create([
            'user_id' => $user->id,
            'transaction_type' => TransactionLog::TYPE_POOL_SHARE,
            'amount' => 148500,
            'description' => 'Chi lại Pool Share ngày 30/05/2026.',
            'notes' => 'Mã: LE-2',
            'status' => TransactionLog::STATUS_SUCCESS,
            'reference_id' => 'LE-2',
        ]);

        $this->actingAs($user)
            ->get('/user/transactions?type=affiliate&q=abc@gmail.com')
            ->assertOk()
            ->assertSee('Lịch sử giao dịch')
            ->assertSee('Hoa hồng affiliate từ user #25 - abc@gmail.com kích hoạt gói.')
            ->assertDontSee('Chi lại Pool Share ngày 30/05/2026.');
    }
}
