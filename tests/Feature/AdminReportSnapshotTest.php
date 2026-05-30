<?php

namespace Tests\Feature;

use App\Models\AdminReportSnapshot;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AdminReportSnapshotService;
use App\Services\PaymentProcessor;
use App\Services\PoolShareDistributionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminReportSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_command_captures_daily_financials_and_pool_share_rows(): void
    {
        $this->seed();
        Carbon::setTestNow('2026-05-30 11:00:00');

        $plan = Plan::query()->where('code', 'yearly')->firstOrFail();
        $referrer = $this->createUser('ref-a', 'ref-a@example.com', '0901000001', 'Referrer A', true);
        $buyer = $this->createUser('buyer-a', 'buyer-a@example.com', '0901000002', 'Buyer A');

        Subscription::query()->create([
            'user_id' => $referrer->id,
            'plan_id' => $plan->id,
            'starts_at' => now()->subDays(15),
            'ends_at' => now()->addDays(30),
            'status' => 'active',
        ]);

        Referral::query()->create([
            'referrer_id' => $referrer->id,
            'referred_id' => $buyer->id,
            'activated_at' => null,
        ]);

        for ($i = 0; $i < 9; $i++) {
            $referred = $this->createUser(
                "ref-a-child-{$i}",
                "ref-a-child-{$i}@example.com",
                '0912000'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                "Child {$i}"
            );

            Referral::query()->create([
                'referrer_id' => $referrer->id,
                'referred_id' => $referred->id,
                'activated_at' => now()->subDays(2),
            ]);
        }

        $processor = app(PaymentProcessor::class);
        $order = $processor->createOrder($buyer->id, $plan->id, (int) $plan->price_vnd);
        $processor->complete($order, 'BANK-TXN-001', '2026-05-30 11:00:00');

        Carbon::setTestNow('2026-05-30 23:59:00');
        app(PoolShareDistributionService::class)->distribute(Carbon::parse('2026-05-30'));
        $snapshot = app(AdminReportSnapshotService::class)->capture(Carbon::parse('2026-05-30'));

        $this->assertSame('2026-05-30', $snapshot->report_date->toDateString());
        $this->assertSame(1, $snapshot->new_paid_members_count);
        $this->assertSame(1, $snapshot->activation_count);
        $this->assertSame(1500000, $snapshot->gross_sales_vnd);
        $this->assertSame(450000, $snapshot->affiliate_commission_vnd);
        $this->assertSame(150000, $snapshot->vat_vnd);
        $this->assertSame(675000, $snapshot->company_revenue_vnd);
        $this->assertSame(225000, $snapshot->pool_share_in_vnd);
        $this->assertSame(74925, $snapshot->pool_share_distributed_vnd);
        $this->assertSame(150075, $snapshot->shared_pool_balance_vnd);
        $this->assertSame(1, $snapshot->poolShareRows()->count());
        $this->assertSame(5, $snapshot->logs()->count());

        $groupA = $snapshot->pool_group_stats['A'];
        $this->assertSame(1, $groupA['qualified_count']);
        $this->assertSame(74925, $groupA['group_total_vnd']);
        $this->assertSame(74925, $groupA['amount_each_vnd']);

        $row = $snapshot->poolShareRows()->firstOrFail();
        $this->assertSame('A', $row->group_code);
        $this->assertSame(10, $row->active_referrals_count);
        $this->assertSame(74925, $row->payout_vnd);

        Carbon::setTestNow();
    }

    public function test_report_page_and_export_use_snapshot_data(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $snapshot = AdminReportSnapshot::query()->create([
            'report_date' => '2026-05-29',
            'captured_at' => Carbon::parse('2026-05-29 23:59:00'),
            'new_paid_members_count' => 20,
            'activation_count' => 22,
            'gross_sales_vnd' => 30000000,
            'affiliate_commission_vnd' => 9000000,
            'vat_vnd' => 3000000,
            'company_revenue_vnd' => 13500000,
            'pool_share_in_vnd' => 20000000,
            'pool_share_distributed_vnd' => 20000000,
            'shared_pool_balance_vnd' => 0,
            'pool_group_stats' => [
                'A' => ['min' => 10, 'max' => 49, 'share_bp' => 3330, 'qualified_count' => 30, 'group_total_vnd' => 9990000, 'amount_each_vnd' => 333000],
                'B' => ['min' => 50, 'max' => 99, 'share_bp' => 3330, 'qualified_count' => 10, 'group_total_vnd' => 9990000, 'amount_each_vnd' => 999000],
                'C' => ['min' => 100, 'max' => null, 'share_bp' => 3340, 'qualified_count' => 5, 'group_total_vnd' => 10020000, 'amount_each_vnd' => 2004000],
            ],
        ]);

        $snapshot->poolShareRows()->create([
            'user_id' => 88,
            'name' => 'Nguyễn Minh Anh',
            'email' => 'minhanh@example.com',
            'group_code' => 'A',
            'active_referrals_count' => 18,
            'payout_vnd' => 333000,
            'account_status' => 'Gói còn hiệu lực',
            'subscription_ends_at' => Carbon::parse('2026-06-29 23:59:59'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Report báo cáo ngày')
            ->assertSee('Nguyễn Minh Anh')
            ->assertSee('30.000.000 đ');

        $this->actingAs($admin)
            ->get(route('admin.reports.export', $snapshot))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename=pool-share-report-2026-05-29.xlsx');
    }

    public function test_snapshot_service_keeps_only_latest_ten_days(): void
    {
        $this->seed();

        for ($i = 0; $i < 10; $i++) {
            AdminReportSnapshot::query()->create([
                'report_date' => Carbon::parse('2026-05-10')->addDays($i)->toDateString(),
                'captured_at' => now(),
            ]);
        }

        app(AdminReportSnapshotService::class)->capture(Carbon::parse('2026-05-30'));

        $snapshots = AdminReportSnapshot::query()->orderBy('report_date')->get();

        $this->assertCount(10, $snapshots);
        $this->assertDatabaseMissing('admin_report_snapshots', ['report_date' => '2026-05-10']);
        $this->assertTrue($snapshots->contains(fn (AdminReportSnapshot $snapshot) => $snapshot->report_date->toDateString() === '2026-05-30'));
    }

    private function createUser(string $username, string $email, string $phone, string $name, bool $withProfile = true): User
    {
        $user = User::query()->create([
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make('password'),
            'trial_started_at' => now()->subDays(40),
        ]);

        if ($withProfile) {
            $user->profile()->create([
                'accepted_terms' => true,
                'accepted_terms_at' => now(),
            ]);
        }

        ReferralLink::query()->create([
            'user_id' => $user->id,
            'code' => strtoupper($username),
        ]);

        return $user;
    }
}
