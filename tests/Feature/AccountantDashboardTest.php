<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\TransactionLog;
use App\Models\User;
use App\Services\WalletLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountantDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_login_redirects_to_accountant_dashboard(): void
    {
        $this->seed();

        $this->post('/login', [
            'login' => 'accountant@example.com',
            'password' => 'password',
        ])->assertRedirect(route('accountant.dashboard'));
    }

    public function test_accountant_can_view_financial_pages_and_user_cannot(): void
    {
        $this->seed();

        $accountant = User::query()->where('email', 'accountant@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        foreach ([
            route('accountant.dashboard'),
            route('accountant.transactions.index'),
            route('accountant.withdrawals.index'),
            route('accountant.deposits.index'),
            route('accountant.wallets.index'),
            route('accountant.revenue'),
            route('accountant.reports'),
            route('accountant.audit-logs'),
        ] as $url) {
            $this->actingAs($accountant)->get($url)->assertOk();
        }

        $this->actingAs($user)->get(route('accountant.dashboard'))->assertForbidden();
    }

    public function test_accountant_can_adjust_and_lock_wallet_with_audit_logs(): void
    {
        $this->seed();

        $accountant = User::query()->where('email', 'accountant@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($accountant)
            ->post(route('accountant.wallets.adjust', $user), [
                'direction' => 'add',
                'amount_vnd' => 250000,
                'note' => 'Manual topup',
            ])
            ->assertRedirect();

        $this->assertSame(250000, app(WalletLedgerService::class)->walletForUser($user)->fresh()->balance_vnd);
        $this->assertDatabaseHas('accountant_audit_logs', [
            'actor_user_id' => $accountant->id,
            'action' => 'wallet.adjust.add',
        ]);

        $this->actingAs($accountant)
            ->post(route('accountant.wallets.toggle-lock', $user))
            ->assertRedirect();

        $this->assertTrue(app(WalletLedgerService::class)->walletForUser($user)->fresh()->is_locked);
        $this->assertDatabaseHas('accountant_audit_logs', [
            'actor_user_id' => $accountant->id,
            'action' => 'wallet.lock',
        ]);
    }

    public function test_locked_wallet_blocks_withdrawal_and_wallet_checkout(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'yearly')->firstOrFail();
        $wallets = app(WalletLedgerService::class);
        $wallet = $wallets->walletForUser($user);
        $wallets->credit($wallet, 500000, 'seed_topup');
        $wallet->update(['is_locked' => true]);

        $this->actingAs($user)->post('/wallet/bank-account', [
            'bank_name' => 'MB Bank',
            'account_number' => '123456789',
            'account_holder' => 'NGUYEN VAN A',
        ])->assertRedirect();

        $this->actingAs($user)
            ->from('/wallet')
            ->post(route('wallet.withdraw'), [
                'bank_account_id' => $user->fresh()->bankAccount->id,
                'amount_vnd' => '100.000',
            ])
            ->assertRedirect('/wallet')
            ->assertSessionHasErrors('amount_vnd');

        $this->actingAs($user)
            ->from('/billing')
            ->post(route('billing.orders.store'), [
                'plan_id' => $plan->id,
                'payment_method' => 'wallet',
            ])
            ->assertRedirect('/billing')
            ->assertSessionHasErrors('payment_method');
    }

    public function test_accountant_export_routes_return_downloads(): void
    {
        $this->seed();

        $accountant = User::query()->where('email', 'accountant@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        TransactionLog::query()->create([
            'user_id' => $user->id,
            'transaction_type' => TransactionLog::TYPE_MONEY_IN,
            'amount' => 500000,
            'description' => 'Nap vi',
            'notes' => 'test',
            'status' => TransactionLog::STATUS_SUCCESS,
            'reference_id' => 'TXN00001',
        ]);

        $this->actingAs($accountant)
            ->get(route('accountant.transactions.export', ['format' => 'csv']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($accountant)
            ->get(route('accountant.reports.export', ['format' => 'csv']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
