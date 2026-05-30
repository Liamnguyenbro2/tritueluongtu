<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentProcessor;
use App\Services\WalletLedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionAndPoolShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_upgrade_splits_affiliate_company_and_pool_share_percentages(): void
    {
        $this->seed();
        Carbon::setTestNow('2026-05-25 10:30:00');

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $referrer = User::query()->where('email', 'user@example.com')->firstOrFail();
        $buyer = User::query()->create([
            'username' => 'buyer',
            'name' => 'User B',
            'email' => 'buyer@example.com',
            'phone' => '0999000001',
            'password' => 'password',
            'trial_started_at' => now(),
        ]);
        $plan = Plan::query()->where('code', 'yearly')->firstOrFail();
        $wallets = app(WalletLedgerService::class);

        Referral::query()->create([
            'referrer_id' => $referrer->id,
            'referred_id' => $buyer->id,
        ]);

        $order = app(PaymentProcessor::class)->createOrder($buyer->id, $plan->id, 1500000);
        app(PaymentProcessor::class)->complete($order, 'BANK-AFF', now()->toIso8601String());

        $this->assertSame(450000, $wallets->walletForUser($referrer)->fresh()->balance_vnd);
        $this->assertSame(825000, $wallets->walletForUser($admin)->fresh()->balance_vnd);
        $this->assertSame(225000, $wallets->systemWallet('shared_pool')->fresh()->balance_vnd);

        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallets->walletForUser($referrer)->id,
            'amount_vnd' => 450000,
            'type' => 'referral_commission',
            'memo' => 'Hoa hồng affiliate #'.$buyer->id.' - buyer@example.com kích hoạt - 25/05/2026 | 10:30',
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallets->walletForUser($admin)->id,
            'amount_vnd' => 150000,
            'type' => 'company_vat',
            'memo' => 'Ghi nhận phí VAT #'.$buyer->id.' - buyer@example.com kích hoạt - 25/05/2026 | 10:30',
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallets->walletForUser($admin)->id,
            'amount_vnd' => 675000,
            'type' => 'company_revenue',
            'memo' => 'Doanh thu do tài khoản #'.$buyer->id.' - buyer@example.com kích hoạt - 25/05/2026 | 10:30',
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallets->systemWallet('shared_pool')->id,
            'amount_vnd' => 225000,
            'type' => 'payment_shared_pool',
            'memo' => 'Ghi nhận Pool Share #'.$buyer->id.' - buyer@example.com kích hoạt - 25/05/2026 | 10:30',
        ]);
        $this->assertDatabaseMissing('ledger_entries', ['type' => 'payment_cashback']);

        Carbon::setTestNow();
    }

    public function test_pool_share_distribution_pays_groups_and_excludes_trial_accounts(): void
    {
        $this->seed();
        Carbon::setTestNow('2026-05-25 23:59:00');

        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $wallets = app(WalletLedgerService::class);
        $groupA = $this->eligibleReferrer('pool-a', 10, $plan);
        $groupB = $this->eligibleReferrer('pool-b', 50, $plan);
        $groupC = $this->eligibleReferrer('pool-c', 100, $plan);
        $trialOnly = $this->referrerWithoutPaidPlan('pool-trial', 10);

        $wallets->credit($wallets->systemWallet('shared_pool'), 20000000, 'payment_shared_pool');

        $this->artisan('pool-share:distribute 2026-05-25')->assertSuccessful();

        $this->assertSame(6660000, $wallets->walletForUser($groupA)->fresh()->balance_vnd);
        $this->assertSame(6660000, $wallets->walletForUser($groupB)->fresh()->balance_vnd);
        $this->assertSame(6680000, $wallets->walletForUser($groupC)->fresh()->balance_vnd);
        $this->assertSame(0, $wallets->walletForUser($trialOnly)->fresh()->balance_vnd);
        $this->assertSame(0, $wallets->systemWallet('shared_pool')->fresh()->balance_vnd);

        foreach ([$groupA, $groupB, $groupC] as $user) {
            $this->assertDatabaseHas('ledger_entries', [
                'wallet_id' => $wallets->walletForUser($user)->id,
                'type' => 'pool_share_payout',
                'memo' => 'Chi lại Pool Share 25/05/2026',
            ]);
        }

        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallets->systemWallet('shared_pool')->id,
            'amount_vnd' => -20000000,
            'type' => 'pool_share_distribution_out',
            'memo' => 'Chi lại Pool Share 25/05/2026',
        ]);

        Carbon::setTestNow();
    }

    public function test_pool_share_distribution_keeps_unqualified_group_funds_in_shared_pool(): void
    {
        $this->seed();
        Carbon::setTestNow('2026-05-25 23:59:00');

        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $wallets = app(WalletLedgerService::class);
        $groupA = $this->eligibleReferrer('only-a', 10, $plan);

        $wallets->credit($wallets->systemWallet('shared_pool'), 450000, 'payment_shared_pool');

        $this->artisan('pool-share:distribute 2026-05-25')->assertSuccessful();

        $this->assertSame(149850, $wallets->walletForUser($groupA)->fresh()->balance_vnd);
        $this->assertSame(300150, $wallets->systemWallet('shared_pool')->fresh()->balance_vnd);
        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallets->systemWallet('shared_pool')->id,
            'amount_vnd' => -149850,
            'type' => 'pool_share_distribution_out',
            'memo' => 'Chi lại Pool Share 25/05/2026',
        ]);

        Carbon::setTestNow();
    }

    public function test_weekly_pool_share_refund_moves_remaining_balance_to_admin_wallet(): void
    {
        $this->seed();
        Carbon::setTestNow('2026-05-31 23:59:00');

        $wallets = app(WalletLedgerService::class);
        $sharedPool = $wallets->systemWallet('shared_pool');
        $adminWallet = $wallets->systemWallet('admin');

        $wallets->credit($sharedPool, 300150, 'payment_shared_pool');

        $this->artisan('pool-share:refund-weekly 2026-05-31')->assertSuccessful();

        $this->assertSame(0, $sharedPool->fresh()->balance_vnd);
        $this->assertSame(300150, $adminWallet->fresh()->balance_vnd);
        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $sharedPool->id,
            'amount_vnd' => -300150,
            'type' => 'pool_share_weekly_refund_out',
            'memo' => 'Hoàn trả Pool Share còn lại dư tuần 31/05/2026',
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $adminWallet->id,
            'amount_vnd' => 300150,
            'type' => 'pool_share_weekly_refund_in',
            'memo' => 'Hoàn trả Pool Share còn lại dư tuần 31/05/2026',
        ]);

        Carbon::setTestNow();
    }

    private function eligibleReferrer(string $prefix, int $activeReferralCount, Plan $plan): User
    {
        $user = $this->referrerWithoutPaidPlan($prefix, $activeReferralCount);

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'status' => 'active',
        ]);

        return $user;
    }

    private function referrerWithoutPaidPlan(string $prefix, int $activeReferralCount): User
    {
        $user = User::query()->create([
            'username' => $prefix,
            'name' => $prefix,
            'email' => "{$prefix}@example.com",
            'phone' => '09'.str_pad((string) User::query()->count(), 8, '0', STR_PAD_LEFT),
            'password' => 'password',
            'trial_started_at' => now()->subDays(30),
        ]);

        for ($index = 1; $index <= $activeReferralCount; $index++) {
            $referred = User::query()->create([
                'username' => "{$prefix}-ref-{$index}",
                'name' => "{$prefix} referral {$index}",
                'email' => "{$prefix}-ref-{$index}@example.com",
                'phone' => '08'.str_pad((string) User::query()->count(), 8, '0', STR_PAD_LEFT),
                'password' => 'password',
                'trial_started_at' => now()->subDays(20),
            ]);

            Referral::query()->create([
                'referrer_id' => $user->id,
                'referred_id' => $referred->id,
                'activated_at' => now()->subDay(),
            ]);
        }

        return $user;
    }
}
