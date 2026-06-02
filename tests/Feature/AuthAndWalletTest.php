<?php

namespace Tests\Feature;

use App\Models\AccountSuspension;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\User;
use App\Services\WalletLedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_terms(): void
    {
        $this->seed();

        $this->post('/register', [
            'username' => 'newuser',
            'name' => 'New User',
            'email' => 'new@example.com',
            'phone' => '0922222222',
            'password' => 'Password1@',
            'password_confirmation' => 'Password1@',
        ])->assertSessionHasErrors('accepted_terms');
    }

    public function test_registration_validates_account_id_email_phone_and_referral_code(): void
    {
        $this->seed();

        $this->from('/register')->post('/register', [
            'username' => 'admin',
            'name' => 'New User',
            'email' => 'not-an-email',
            'phone' => '123456',
            'referral_code' => 'UNKNOWN',
            'password' => 'Password1@',
            'password_confirmation' => 'Password1@',
            'accepted_terms' => '1',
        ])->assertRedirect('/register')
            ->assertSessionHasErrors([
                'username' => 'ID này đã tồn tại.',
                'email' => 'Email phải đúng định dạng có @.',
                'phone' => 'Số điện thoại không đúng vui lòng kiểm tra lại.',
                'referral_code' => 'Mã giới thiệu không tồn tại.',
            ]);

        $this->from('/register')->post('/register', [
            'username' => 'bad-id!',
            'name' => 'New User',
            'email' => 'new@example.com',
            'phone' => '0922222222',
            'password' => 'Password1@',
            'password_confirmation' => 'Password1@',
            'accepted_terms' => '1',
        ])->assertRedirect('/register')
            ->assertSessionHasErrors([
                'username' => 'ID tài khoản chỉ được dùng chữ, số, dấu chấm hoặc dấu gạch dưới.',
            ]);
    }

    public function test_registration_validates_password_strength_rules(): void
    {
        $this->seed();

        $this->from('/register')->post('/register', [
            'username' => 'newuser',
            'name' => 'New User',
            'email' => 'new@example.com',
            'phone' => '0922222222',
            'password' => 'abc123',
            'password_confirmation' => 'abc123',
            'accepted_terms' => '1',
        ])->assertRedirect('/register')
            ->assertSessionHasErrors([
                'password' => 'Mật khẩu phải bao gồm chữ hoa và chữ thường.',
            ]);

        $this->from('/register')->post('/register', [
            'username' => 'newuser2',
            'name' => 'New User',
            'email' => 'new2@example.com',
            'phone' => '0933333333',
            'password' => 'Abc123',
            'password_confirmation' => 'Abc123',
            'accepted_terms' => '1',
        ])->assertRedirect('/register')
            ->assertSessionHasErrors([
                'password' => 'Mật khẩu phải có ít nhất 1 ký tự đặc biệt.',
            ]);
    }

    public function test_registration_referral_lookup_returns_referrer_name(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        ReferralLink::query()->updateOrCreate(
            ['user_id' => $admin->id],
            ['code' => 'ADMIN']
        );

        $this->getJson(route('register.referral.lookup', ['code' => 'admin']))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'name' => 'Admin',
            ]);

        $this->getJson(route('register.referral.lookup', ['code' => 'UNKNOWN']))
            ->assertOk()
            ->assertJson([
                'found' => false,
            ]);
    }

    public function test_registration_email_lookup_reports_existing_and_available_addresses(): void
    {
        $this->seed();

        $this->getJson(route('register.email.lookup', ['email' => 'user@example.com']))
            ->assertOk()
            ->assertJson([
                'exists' => true,
            ]);

        $this->getJson(route('register.email.lookup', ['email' => 'fresh@example.com']))
            ->assertOk()
            ->assertJson([
                'exists' => false,
            ]);
    }

    public function test_registration_rejects_duplicate_email_even_with_different_casing(): void
    {
        $this->seed();

        $this->from('/register')->post('/register', [
            'username' => 'newuser',
            'name' => 'New User',
            'email' => 'USER@EXAMPLE.COM',
            'phone' => '0922222222',
            'password' => 'Password1@',
            'password_confirmation' => 'Password1@',
            'accepted_terms' => '1',
        ])->assertRedirect('/register')
            ->assertSessionHasErrors([
                'email' => 'Email này đã được sử dụng.',
            ]);

        $this->assertSame(1, User::query()->whereRaw('LOWER(email) = ?', ['user@example.com'])->count());
    }

    public function test_registration_stores_email_in_lowercase(): void
    {
        $this->seed();

        $this->post('/register', [
            'username' => 'newuser',
            'name' => 'New User',
            'email' => 'NewUser@Example.COM',
            'phone' => '0922222222',
            'password' => 'Password1@',
            'password_confirmation' => 'Password1@',
            'accepted_terms' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_withdrawal_requires_minimum_amount(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $wallet = app(WalletLedgerService::class)->walletForUser($user);
        app(WalletLedgerService::class)->credit($wallet, 200000, 'test_topup');

        $this->actingAs($user)->post('/wallet/bank-account', [
            'bank_name' => 'MB Bank',
            'account_number' => '123456',
            'account_holder' => 'NGUYEN VAN A',
        ]);

        $this->actingAs($user)->post('/wallet/withdrawals', [
            'bank_account_id' => $user->fresh()->bankAccount->id,
            'amount_vnd' => 99000,
        ])
            ->assertSessionHasErrors([
                'amount_vnd' => 'Số tiền rút tối thiểu là 100.000 đ.',
            ]);
    }

    public function test_withdrawal_cannot_exceed_available_wallet_balance(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $wallet = app(WalletLedgerService::class)->walletForUser($user);
        app(WalletLedgerService::class)->credit($wallet, 51000, 'test_topup');

        $this->actingAs($user)->post('/wallet/bank-account', [
            'bank_name' => 'MB Bank',
            'account_number' => '123456',
            'account_holder' => 'NGUYEN VAN A',
        ]);

        $this->actingAs($user)->from('/wallet')->post('/wallet/withdrawals', [
            'bank_account_id' => $user->fresh()->bankAccount->id,
            'amount_vnd' => '100.000',
        ])
            ->assertRedirect('/wallet')
            ->assertSessionHasErrors([
                'amount_vnd' => 'Số tiền rút không được vượt quá số dư ví hiện có.',
            ]);

        $this->assertSame(51000, $wallet->fresh()->balance_vnd);
        $this->assertDatabaseMissing('withdrawal_requests', [
            'user_id' => $user->id,
            'amount_vnd' => 100000,
        ]);
        $this->assertDatabaseMissing('ledger_entries', [
            'wallet_id' => $wallet->id,
            'type' => 'withdrawal_hold',
            'amount_vnd' => -100000,
        ]);
    }

    public function test_user_can_request_withdrawal_with_formatted_amount(): void
    {
        $this->seed();
        Carbon::setTestNow('2026-05-26 20:20:00');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $wallet = app(WalletLedgerService::class)->walletForUser($user);
        app(WalletLedgerService::class)->credit($wallet, 200000, 'test_topup');

        $this->actingAs($user)->post('/wallet/bank-account', [
            'bank_name' => 'MB Bank',
            'account_number' => '123456',
            'account_holder' => 'NGUYEN VAN A',
        ]);

        $this->actingAs($user)->post('/wallet/withdrawals', [
            'bank_account_id' => $user->fresh()->bankAccount->id,
            'amount_vnd' => '150.000',
        ])->assertRedirect();

        $this->assertDatabaseHas('withdrawal_requests', [
            'user_id' => $user->id,
            'amount_vnd' => 150000,
            'status' => 'pending',
        ]);
        $this->assertSame(50000, $wallet->fresh()->balance_vnd);
        $this->assertDatabaseHas('ledger_entries', [
            'wallet_id' => $wallet->id,
            'amount_vnd' => -150000,
            'type' => 'withdrawal_hold',
            'memo' => 'Tạm giữ yêu cầu rút tiền',
        ]);

        $this->actingAs($user)
            ->get('/wallet')
            ->assertOk()
            ->assertSee('Tạm giữ yêu cầu rút tiền - 26/05/2026 | 20:20');

        Carbon::setTestNow();
    }

    public function test_user_can_change_password_from_profile(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_user_can_save_bank_information_once_from_profile(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)->put('/profile/bank-account', [
            'bank_name' => 'MB Bank',
            'account_number' => '123456789',
            'account_holder' => 'NGUYEN VAN A',
        ])->assertRedirect();

        $this->assertDatabaseHas('bank_accounts', [
            'user_id' => $user->id,
            'bank_name' => 'MB Bank',
            'account_number' => '123456789',
            'account_holder' => 'NGUYEN VAN A',
            'can_edit' => false,
        ]);

        $this->actingAs($user)->put('/profile/bank-account', [
            'bank_name' => 'VCB',
            'account_number' => '999999',
            'account_holder' => 'NGUYEN VAN A',
        ])->assertForbidden();
    }

    public function test_wallet_bank_information_is_locked_after_first_save(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)->post('/wallet/bank-account', [
            'bank_name' => 'MB Bank',
            'account_number' => '123456789',
            'account_holder' => 'NGUYEN VAN A',
        ])->assertRedirect();

        $this->actingAs($user)
            ->get('/wallet')
            ->assertOk()
            ->assertSee('Bạn không được phép thay đổi thông tin tài khoản ngân hàng.')
            ->assertSee('Đã khóa chỉnh sửa')
            ->assertSee('Thông tin này chỉ được nhập một lần duy nhất và không được phép thay đổi sau khi xác nhận.');

        $this->actingAs($user)->post('/wallet/bank-account', [
            'bank_name' => 'VCB',
            'account_number' => '999999',
            'account_holder' => 'NGUYEN VAN A',
        ])->assertForbidden();
    }

    public function test_suspended_user_sees_detailed_login_notice(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        AccountSuspension::query()->create([
            'user_id' => $user->id,
            'type' => 'temporary',
            'reason' => 'Chưa xác minh thông tin',
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
        ]);

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('suspension_notice', function (array $notice) use ($user) {
                return $notice['user_id'] === $user->id
                    && $notice['email'] === $user->email
                    && $notice['type_label'] === 'tạm thời'
                    && $notice['reason'] === 'Chưa xác minh thông tin';
            });

        $this->assertGuest();
    }

    public function test_login_is_temporarily_locked_after_five_failed_attempts(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        Cache::flush();

        for ($i = 0; $i < 4; $i++) {
            $this->from('/login')->post('/login', [
                'login' => $user->email,
                'password' => 'wrong-password',
            ])->assertRedirect('/login');
        }

        $this->from('/login')->post('/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login')
            ->assertSessionHas('login_lock', function (array $lock) use ($user) {
                return $lock['login'] === $user->email
                    && $lock['seconds_remaining'] > 0;
            });

        $this->from('/login')->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login')
            ->assertSessionHas('login_lock');

        $this->assertGuest();
    }

    public function test_account_is_suspended_after_ten_failed_attempts(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        Cache::flush();
        Carbon::setTestNow('2026-05-29 10:00:00');

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'login' => $user->email,
                'password' => 'wrong-password',
            ])->assertRedirect('/login');
        }

        Carbon::setTestNow(now()->addMinutes(16));

        for ($i = 0; $i < 4; $i++) {
            $this->from('/login')->post('/login', [
                'login' => $user->email,
                'password' => 'wrong-password',
            ])->assertRedirect('/login');
        }

        $this->from('/login')->post('/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('suspension_notice', function (array $notice) use ($user) {
                return $notice['user_id'] === $user->id
                    && $notice['email'] === $user->email
                    && $notice['type_label'] === 'tạm thời'
                    && $notice['reason'] === 'Tài khoản bạn bị tạm khóa do nhập sai mật khẩu quá nhiều lần';
            });

        $this->assertDatabaseHas('account_suspensions', [
            'user_id' => $user->id,
            'type' => 'temporary',
            'reason' => 'Tài khoản bạn bị tạm khóa do nhập sai mật khẩu quá nhiều lần',
        ]);

        Carbon::setTestNow();
    }

    public function test_admin_can_unlock_suspended_user(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        AccountSuspension::query()->create([
            'user_id' => $user->id,
            'type' => 'permanent',
            'reason' => 'Vi phạm chính sách',
            'starts_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.unlock', $user))
            ->assertRedirect();

        $this->assertFalse($user->fresh()->activeSuspension()->exists());
    }

    public function test_admin_report_always_shows_account_unlock_control(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.users.report', $user))
            ->assertOk()
            ->assertSee('Tài khoản đang hoạt động')
            ->assertSee('Mở khóa tài khoản');
    }

    public function test_admin_report_all_period_shows_affiliate_and_income_pie_totals(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $wallets = app(WalletLedgerService::class);
        $adminWallet = $wallets->walletForUser($admin);

        Referral::query()->create([
            'referrer_id' => $admin->id,
            'referred_id' => $user->id,
            'activated_at' => now(),
        ]);
        $wallets->credit($adminWallet, 200000, 'referral_commission');
        $wallets->credit($adminWallet, 80000, 'shared_pool_income');

        $this->actingAs($admin)
            ->get(route('admin.users.report', ['user' => $admin, 'period' => 'all']))
            ->assertOk()
            ->assertSee('Đã mời & kích hoạt', false)
            ->assertSee('Affiliate & pool đồng chia', false)
            ->assertSee('200.000 đ')
            ->assertSee('80.000 đ');
    }

    public function test_affiliate_members_mask_contact_info_for_non_admin_and_show_full_for_admin(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $member = User::query()->create([
            'username' => 'tranmeo',
            'name' => 'Trần Mèo',
            'email' => 'tranmeo@gmail.com',
            'phone' => '0983250633',
            'password' => 'password',
            'trial_started_at' => now(),
        ]);

        Referral::query()->create([
            'referrer_id' => $user->id,
            'referred_id' => $member->id,
            'activated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('affiliate.index'))
            ->assertOk()
            ->assertSee('tra***o@gmail.com')
            ->assertSee('098*****33')
            ->assertDontSee('tranmeo@gmail.com')
            ->assertDontSee('0983250633');

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $adminMember = User::query()->create([
            'username' => 'lisapham',
            'name' => 'Lissa Pham',
            'email' => 'lisa@gmail.com',
            'phone' => '08999222111',
            'password' => 'password',
            'trial_started_at' => now(),
        ]);

        Referral::query()->create([
            'referrer_id' => $admin->id,
            'referred_id' => $adminMember->id,
            'activated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('affiliate.index'))
            ->assertOk()
            ->assertSee('lisa@gmail.com')
            ->assertSee('08999222111');
    }
}
