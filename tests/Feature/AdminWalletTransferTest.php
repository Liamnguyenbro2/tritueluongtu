<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\WalletLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminWalletTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_transfer_wallet_balance_to_user(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $wallets = app(WalletLedgerService::class);
        $adminWallet = $wallets->walletForUser($admin);
        $userWallet = $wallets->walletForUser($user);

        $wallets->credit($adminWallet, 500000, 'test_topup');

        $this->actingAs($admin)->post(route('admin.wallet-transfer'), [
            'user_id' => $user->id,
            'amount_vnd' => '200.000',
        ])->assertRedirect();

        $this->assertSame(300000, $adminWallet->fresh()->balance_vnd);
        $this->assertSame(200000, $userWallet->fresh()->balance_vnd);
        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $adminWallet->id,
            'amount_vnd' => -200000,
            'type' => 'admin_transfer_out',
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $userWallet->id,
            'amount_vnd' => 200000,
            'type' => 'admin_transfer_in',
        ]);
    }

    public function test_admin_cannot_transfer_more_than_wallet_balance(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($admin)->from('/admin')->post(route('admin.wallet-transfer'), [
            'user_id' => $user->id,
            'amount_vnd' => 200000,
        ])->assertRedirect('/admin')->assertSessionHasErrors('amount_vnd');
    }

    public function test_admin_can_update_sidebar_branding(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.branding.update'), [
            'brand_logo_url' => 'https://example.com/logo.png',
            'brand_eyebrow' => 'ZEN BRAND',
            'brand_name' => 'Energy Pro',
        ])->assertRedirect();

        $this->assertSame('https://example.com/logo.png', SiteSetting::getValue('brand_logo_url'));
        $this->assertSame('ZEN BRAND', SiteSetting::getValue('brand_eyebrow'));
        $this->assertSame('Energy Pro', SiteSetting::getValue('brand_name'));

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('https://example.com/logo.png', false)
            ->assertSee('ZEN BRAND')
            ->assertSee('Energy Pro');

        Storage::fake('public');

        $this->actingAs($admin)->put(route('admin.branding.update'), [
            'brand_logo_file' => UploadedFile::fake()->createWithContent('logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')),
            'brand_eyebrow' => 'ZEN FILE',
            'brand_name' => 'Energy File',
        ])->assertRedirect();

        $logoUrl = SiteSetting::getValue('brand_logo_url');
        $this->assertStringContainsString('/storage/brand-logos/', $logoUrl);
        $storedPath = ltrim(str_replace('/storage/', '', parse_url($logoUrl, PHP_URL_PATH) ?: $logoUrl), '/');
        Storage::disk('public')->assertExists($storedPath);
        $this->assertSame('ZEN FILE', SiteSetting::getValue('brand_eyebrow'));
        $this->assertSame('Energy File', SiteSetting::getValue('brand_name'));
    }

    public function test_admin_withdrawal_table_shows_account_id_and_email(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $bankAccount = BankAccount::query()->create([
            'user_id' => $user->id,
            'bank_name' => 'MB Bank',
            'account_number' => '123456789',
            'account_holder' => 'NGUYEN VAN A',
            'can_edit' => false,
        ]);

        WithdrawalRequest::query()->create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'amount_vnd' => 100000,
            'status' => 'pending',
        ]);
        WithdrawalRequest::query()->create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'amount_vnd' => 200000,
            'status' => 'approved',
        ]);
        WithdrawalRequest::query()->create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'amount_vnd' => 300000,
            'status' => 'rejected',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('ID tài khoản')
            ->assertSee('Email')
            ->assertSee('#'.$user->id)
            ->assertSee($user->email)
            ->assertSee('Chờ duyệt')
            ->assertSee('Đã duyệt')
            ->assertSee('Từ chối')
            ->assertSee('Lý do từ chối')
            ->assertSee('data-reject-withdrawal', false)
            ->assertSee('Đã xử lý')
            ->assertDontSee('pending')
            ->assertDontSee('approved')
            ->assertDontSee('rejected');
    }

    public function test_admin_approving_withdrawal_does_not_debit_twice(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $wallet = app(WalletLedgerService::class)->walletForUser($user);
        app(WalletLedgerService::class)->credit($wallet, 200000, 'test_topup');
        $bankAccount = BankAccount::query()->create([
            'user_id' => $user->id,
            'bank_name' => 'MB Bank',
            'account_number' => '123456789',
            'account_holder' => 'NGUYEN VAN A',
            'can_edit' => false,
        ]);

        $this->actingAs($user)->post('/wallet/withdrawals', [
            'bank_account_id' => $bankAccount->id,
            'amount_vnd' => '100.000',
        ]);

        $withdrawal = WithdrawalRequest::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(100000, $wallet->fresh()->balance_vnd);

        $this->actingAs($admin)
            ->post(route('admin.withdrawals.approve', $withdrawal))
            ->assertRedirect();

        $this->assertSame(100000, $wallet->fresh()->balance_vnd);
        $this->assertSame('approved', $withdrawal->fresh()->status);
        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallet->id,
            'amount_vnd' => -100000,
            'type' => 'withdrawal_completed',
            'memo' => 'Đã hoàn tất việc rút tiền',
        ]);
        $this->assertDatabaseMissing('ledger_entries', [
            'wallet_id' => $wallet->id,
            'type' => 'withdrawal_payout',
        ]);

        $this->actingAs($user)
            ->get('/wallet')
            ->assertOk()
            ->assertSee('Rút tiền hoàn tất')
            ->assertSee('Đã hoàn tất việc rút tiền')
            ->assertDontSee('withdrawal_payout')
            ->assertDontSee('Tạm giữ yêu cầu rút tiền');
    }

    public function test_admin_rejecting_withdrawal_requires_reason_and_refunds_hold(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $wallet = app(WalletLedgerService::class)->walletForUser($user);
        app(WalletLedgerService::class)->credit($wallet, 200000, 'test_topup');
        $bankAccount = BankAccount::query()->create([
            'user_id' => $user->id,
            'bank_name' => 'MB Bank',
            'account_number' => '123456789',
            'account_holder' => 'NGUYEN VAN A',
            'can_edit' => false,
        ]);

        $this->actingAs($user)->post('/wallet/withdrawals', [
            'bank_account_id' => $bankAccount->id,
            'amount_vnd' => '100.000',
        ]);

        $withdrawal = WithdrawalRequest::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(100000, $wallet->fresh()->balance_vnd);

        $this->actingAs($admin)
            ->from('/admin')
            ->post(route('admin.withdrawals.reject', $withdrawal), [])
            ->assertRedirect('/admin')
            ->assertSessionHasErrors('admin_note');

        $this->actingAs($admin)
            ->post(route('admin.withdrawals.reject', $withdrawal), [
                'admin_note' => 'Sai thông tin ngân hàng',
            ])
            ->assertRedirect();

        $this->assertSame(200000, $wallet->fresh()->balance_vnd);
        $this->assertSame('rejected', $withdrawal->fresh()->status);
        $this->assertSame('Sai thông tin ngân hàng', $withdrawal->fresh()->admin_note);
        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallet->id,
            'amount_vnd' => 100000,
            'type' => 'withdrawal_refund',
            'memo' => 'Hoàn tiền yêu cầu rút bị từ chối: Sai thông tin ngân hàng',
        ]);
    }
}
