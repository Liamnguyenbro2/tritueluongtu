<?php

namespace Tests\Feature;

use App\Models\KycVerification;
use App\Models\Lesson;
use App\Models\Plan;
use App\Models\TransactionLog;
use App\Models\User;
use App\Services\PaymentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KycVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_paid_subscription_is_not_forced_to_complete_kyc(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('action="'.route('kyc.store').'"', false);
    }

    public function test_paid_user_sees_forced_kyc_modal_on_dashboard(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'yearly')->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder($user->id, $plan->id, $plan->price_vnd);
        app(PaymentProcessor::class)->complete($order, 'BANK-KYC-1');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('action="'.route('kyc.store').'"', false)
            ->assertSeeText('KYC');
    }

    public function test_paid_user_cannot_use_paid_lesson_until_kyc_is_completed(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'yearly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->orderBy('position')->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder($user->id, $plan->id, $plan->price_vnd);
        app(PaymentProcessor::class)->complete($order, 'BANK-KYC-2');

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('lessons.toggle', $lesson))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('kyc');
    }

    public function test_user_can_submit_kyc_and_unlock_paid_access(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'yearly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->orderBy('position')->firstOrFail();
        $order = app(PaymentProcessor::class)->createOrder($user->id, $plan->id, $plan->price_vnd);
        app(PaymentProcessor::class)->complete($order, 'BANK-KYC-3');

        $this->actingAs($user)
            ->post(route('kyc.store'), [
                'full_name' => 'Nguyen Van A',
                'citizen_id' => '012345678901',
                'address' => '123 Duong ABC, Phuong DEF, TPHCM',
            ])
            ->assertRedirect(route('kyc.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('kyc_verifications', [
            'user_id' => $user->id,
            'full_name' => 'Nguyen Van A',
            'citizen_id' => '012345678901',
        ]);

        $this->assertDatabaseHas('transaction_logs', [
            'user_id' => $user->id,
            'transaction_type' => TransactionLog::TYPE_OTHER,
            'amount' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('lessons.toggle', $lesson))
            ->assertRedirect();
    }

    public function test_admin_can_view_search_and_export_kyc_records(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        KycVerification::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Nguyen Van A',
            'citizen_id' => '012345678901',
            'address' => '123 Duong ABC',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.kyc.index', ['q' => '012345678901']))
            ->assertOk()
            ->assertSeeText('Nguyen Van A')
            ->assertSeeText('012345678901');

        $export = $this->actingAs($admin)
            ->get(route('admin.kyc.export', ['q' => '012345678901']));

        $export
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('012345678901', $export->streamedContent());
    }
}
