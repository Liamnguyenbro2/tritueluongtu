<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\WalletLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

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
        $this->actingAs($user)->get(route('accountant.deposits.export'))->assertForbidden();
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

    public function test_accountant_can_filter_deposits_by_user_status_and_date(): void
    {
        $this->seed();

        $accountant = User::query()->where('email', 'accountant@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $user->update(['username' => 'liamnguyen']);
        $user->kycVerification()->create([
            'full_name' => 'Nguyen Van A',
            'citizen_id' => '012345678901',
            'address' => '123 Duong ABC',
            'submitted_at' => now(),
        ]);
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();

        DB::table('payment_orders')->insert([
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'code' => 'DEP-OK-001',
                'amount_vnd' => 49000,
                'status' => 'paid',
                'provider_transaction_id' => 'REF-OK-001',
                'paid_at' => now()->subDay(),
                'metadata' => json_encode(['payment_method' => 'wallet', 'bank_name' => 'MB Bank']),
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'code' => 'DEP-FAIL-001',
                'amount_vnd' => 99000,
                'status' => 'failed',
                'provider_transaction_id' => 'REF-FAIL-001',
                'paid_at' => null,
                'metadata' => json_encode(['payment_method' => 'bank_qr', 'bank_name' => 'ACB']),
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ]);

        $response = $this->actingAs($accountant)->get(route('accountant.deposits.index', [
            'user' => 'liamnguyen',
            'status' => 'paid',
            'from_date' => now()->subDay()->format('Y-m-d'),
            'to_date' => now()->subDay()->format('Y-m-d'),
        ]));

        $response->assertOk()
            ->assertSee('DEP-OK-001')
            ->assertSee('REF-OK-001')
            ->assertSee('liamnguyen')
            ->assertSee('012345678901')
            ->assertSee('Nguyen Van A')
            ->assertSee('123 Duong ABC')
            ->assertDontSee('DEP-FAIL-001');
    }

    public function test_accountant_can_export_deposits_xlsx_with_current_filters(): void
    {
        $this->seed();

        $accountant = User::query()->where('email', 'accountant@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $user->update(['username' => 'liamnguyen']);
        $user->kycVerification()->create([
            'full_name' => 'Nguyen Van A',
            'citizen_id' => '012345678901',
            'address' => '123 Duong ABC',
            'submitted_at' => now(),
        ]);
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();

        DB::table('payment_orders')->insert([
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'code' => 'DEP-EXPORT-001',
                'amount_vnd' => 49000,
                'status' => 'paid',
                'provider_transaction_id' => 'REF-EXPORT-001',
                'paid_at' => now(),
                'metadata' => json_encode(['payment_method' => 'wallet', 'bank_name' => 'MB Bank']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'code' => 'DEP-EXPORT-002',
                'amount_vnd' => 99000,
                'status' => 'failed',
                'provider_transaction_id' => 'REF-EXPORT-002',
                'paid_at' => null,
                'metadata' => json_encode(['payment_method' => 'bank_qr', 'bank_name' => 'ACB']),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
        ]);

        $response = $this->actingAs($accountant)
            ->get(route('accountant.deposits.export', [
                'user' => 'liamnguyen',
                'status' => 'paid',
                'from_date' => now()->format('Y-m-d'),
                'to_date' => now()->format('Y-m-d'),
            ]));

        $response
            ->assertOk()
            ->assertDownload('deposits_'.now()->format('Ymd').'.xlsx');

        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $stylesXml = $zip->getFromName('xl/styles.xml');
        $zip->close();

        $this->assertNotFalse($sheetXml);
        $this->assertNotFalse($stylesXml);
        $this->assertStringContainsString('DEP-EXPORT-001', $sheetXml);
        $this->assertStringContainsString('REF-EXPORT-001', $sheetXml);
        $this->assertStringContainsString('liamnguyen', $sheetXml);
        $this->assertStringContainsString('012345678901', $sheetXml);
        $this->assertStringContainsString('Nguyen Van A', $sheetXml);
        $this->assertStringContainsString('123 Duong ABC', $sheetXml);
        $this->assertStringContainsString('49.000', $sheetXml);
        $this->assertStringContainsString('MB Bank', $sheetXml);
        $this->assertStringNotContainsString('DEP-EXPORT-002', $sheetXml);
        $this->assertStringContainsString('fontId="1"', $stylesXml);

        $this->assertDatabaseHas('accountant_audit_logs', [
            'actor_user_id' => $accountant->id,
            'action' => 'deposits.export.xlsx',
        ]);
    }

    public function test_deposit_filters_reject_invalid_date_ranges(): void
    {
        $this->seed();

        $accountant = User::query()->where('email', 'accountant@example.com')->firstOrFail();

        $this->actingAs($accountant)
            ->from(route('accountant.deposits.index'))
            ->get(route('accountant.deposits.index', [
                'from_date' => now()->format('Y-m-d'),
                'to_date' => now()->subDay()->format('Y-m-d'),
            ]))
            ->assertRedirect(route('accountant.deposits.index'))
            ->assertSessionHasErrors('from_date');

        $this->actingAs($accountant)
            ->from(route('accountant.deposits.index'))
            ->get(route('accountant.deposits.index', [
                'from_date' => now()->subDays(30)->format('Y-m-d'),
                'to_date' => now()->format('Y-m-d'),
            ]))
            ->assertRedirect(route('accountant.deposits.index'))
            ->assertSessionHasErrors('from_date');
    }

    public function test_accountant_can_export_withdrawals_xlsx_for_recent_date_and_audit_is_logged(): void
    {
        $this->seed();

        $accountant = User::query()->where('email', 'accountant@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $user->kycVerification()->create([
            'full_name' => 'Nguyen Van A',
            'citizen_id' => '012345678901',
            'address' => '123 Duong ABC',
            'submitted_at' => now(),
        ]);

        $bankAccount = DB::table('bank_accounts')->where('user_id', $user->id)->first();
        if (! $bankAccount) {
            $this->actingAs($user)->post('/wallet/bank-account', [
                'bank_name' => 'MB Bank',
                'account_number' => '123456789',
                'account_holder' => 'NGUYEN VAN A',
            ]);
            $bankAccount = DB::table('bank_accounts')->where('user_id', $user->id)->first();
        }

        WithdrawalRequest::query()->create([
            'withdrawal_number' => 1,
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'amount_vnd' => 100000,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($accountant)
            ->get(route('accountant.withdrawals.export', ['export_date' => now()->format('Y-m-d')]));

        $response
            ->assertOk()
            ->assertDownload('withdrawals_'.now()->format('d-m-Y').'.xlsx');

        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $stylesXml = $zip->getFromName('xl/styles.xml');
        $zip->close();

        $this->assertNotFalse($sheetXml);
        $this->assertNotFalse($stylesXml);
        $this->assertStringContainsString('012345678901', $sheetXml);
        $this->assertStringContainsString('Nguyen Van A', $sheetXml);
        $this->assertStringContainsString('MB Bank', $sheetXml);
        $this->assertStringContainsString('123456789', $sheetXml);
        $this->assertStringContainsString('<cols>', $sheetXml);
        $this->assertStringContainsString('fontId="1"', $stylesXml);

        $this->assertDatabaseHas('accountant_audit_logs', [
            'actor_user_id' => $accountant->id,
            'action' => 'withdrawal.export.xlsx',
        ]);
    }

    public function test_withdrawal_export_rejects_dates_older_than_seven_days(): void
    {
        $this->seed();

        $accountant = User::query()->where('email', 'accountant@example.com')->firstOrFail();

        $this->actingAs($accountant)
            ->from(route('accountant.withdrawals.index'))
            ->get(route('accountant.withdrawals.export', ['export_date' => now()->subDays(7)->format('Y-m-d')]))
            ->assertRedirect(route('accountant.withdrawals.index'))
            ->assertSessionHasErrors('export_date');
    }
}