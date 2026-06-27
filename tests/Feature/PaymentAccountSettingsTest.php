<?php

namespace Tests\Feature;

use App\Models\PaymentOrder;
use App\Models\Plan;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PaymentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentAccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_payment_account_and_new_qr_uses_saved_settings(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.payment-settings.update'), [
                'payment_bank_name' => 'MBBANK',
                'payment_bank_code' => 'MBBANK',
                'payment_account_no' => '9969279668',
                'payment_account_name' => 'CTY TNHH KET NOI TRI TUE LUONG TU',
            ])
            ->assertRedirect();

        $this->assertSame([
            'bank_name' => 'MBBANK',
            'bank_code' => 'MBBANK',
            'account_no' => '9969279668',
            'account_name' => 'CTY TNHH KET NOI TRI TUE LUONG TU',
        ], SiteSetting::paymentAccount());

        $plan = Plan::query()->where('code', 'yearly')->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder($user->id, $plan->id, (int) $plan->price_vnd);
        $qrUrl = $order->vietQrImageUrl();

        $this->assertNotNull($qrUrl);
        $this->assertStringContainsString('/MBBANK-9969279668-compact2.png', $qrUrl);
        $this->assertStringContainsString('accountName=CTY%20TNHH%20KET%20NOI%20TRI%20TUE%20LUONG%20TU', $qrUrl);
        $this->assertSame('9969279668', data_get($order->metadata, 'qr.account_no'));
    }

    public function test_regular_user_cannot_update_payment_account(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)
            ->put(route('admin.payment-settings.update'), [
                'payment_bank_name' => 'OTHER',
                'payment_bank_code' => 'OTHER',
                'payment_account_no' => '1234567890',
                'payment_account_name' => 'OTHER ACCOUNT',
            ])
            ->assertForbidden();

        $this->assertSame('9969279668', SiteSetting::getValue('payment_account_no'));
    }

    public function test_regular_user_can_create_and_view_own_qr_order(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'yearly')->firstOrFail();

        $this->actingAs($user)
            ->post(route('billing.orders.store'), [
                'plan_id' => $plan->id,
                'payment_method' => 'bank_qr',
            ])
            ->assertRedirect();

        $order = PaymentOrder::query()->where('user_id', $user->id)->latest('id')->firstOrFail();

        $this->assertIsInt($order->user_id);
        $this->actingAs($user)
            ->get(route('billing.orders.show', $order))
            ->assertOk()
            ->assertSee($order->code)
            ->assertSeeText('Lưu mã QR về máy')
            ->assertSeeText('Lưu hình ảnh');

        $this->actingAs($user)
            ->get(route('billing.orders.status', $order))
            ->assertOk()
            ->assertJson(['status' => 'pending']);
    }

    public function test_user_can_download_only_their_own_order_qr_image(): void
    {
        $this->seed();
        Http::fake([
            '*' => Http::response('fake-png-content', 200, ['Content-Type' => 'image/png']),
        ]);

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $otherUser = User::query()->create([
            'username' => 'otheruser',
            'name' => 'Other User',
            'email' => 'other@example.com',
            'phone' => '0900000001',
            'password' => 'Password1!',
            'is_admin' => false,
            'role' => 'user',
        ]);
        $plan = Plan::query()->where('code', 'yearly')->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder($user->id, $plan->id, (int) $plan->price_vnd);

        $this->actingAs($user)
            ->get(route('billing.orders.qr-image', [$order, 'download' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Content-Disposition', 'attachment; filename="vietqr-'.$order->code.'.png"')
            ->assertContent('fake-png-content');

        $this->actingAs($otherUser)
            ->get(route('billing.orders.qr-image', $order))
            ->assertForbidden();
    }
}
