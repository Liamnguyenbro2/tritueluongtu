<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TransactionLog;
use App\Models\User;
use App\Services\WalletLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_paginated_user_management_list_with_latest_users_first(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $monthlyPlan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $yearlyPlan = Plan::query()->where('code', 'yearly')->firstOrFail();
        $wallets = app(WalletLedgerService::class);

        $users = collect();

        foreach (range(1, 11) as $index) {
            $user = User::query()->create([
                'username' => 'user'.$index,
                'name' => 'User '.$index,
                'email' => "user{$index}@example.com",
                'phone' => '09800000'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'password' => 'password',
                'role' => 'user',
            ]);

            $user->timestamps = false;
            $user->update([
                'created_at' => now()->addMinutes($index),
                'updated_at' => now()->addMinutes($index),
            ]);

            $wallets->walletForUser($user)->update(['balance_vnd' => $index * 1000]);
            $users->push($user);
        }

        Subscription::query()->create([
            'user_id' => $users[10]->id,
            'plan_id' => $monthlyPlan->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(29),
            'status' => 'active',
            'grants_full_library' => false,
        ]);

        Subscription::query()->create([
            'user_id' => $users[9]->id,
            'plan_id' => $yearlyPlan->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(364),
            'status' => 'active',
            'grants_full_library' => true,
        ]);

        $latestUser = $users[10];

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response
            ->assertOk()
            ->assertSeeText('Quản trị user')
            ->assertSeeText('Gói tháng')
            ->assertSeeText('Gói năm')
            ->assertViewHas('stats', function ($stats) {
                return array_key_exists('total', $stats)
                    && array_key_exists('inactive', $stats)
                    && array_key_exists('monthly', $stats)
                    && array_key_exists('yearly', $stats);
            })
            ->assertViewHas('users', function ($paginator) use ($latestUser) {
                $items = collect($paginator->items());

                return $paginator->perPage() === 10
                    && $paginator->total() >= 11
                    && $items->count() === 10
                    && $items->contains(fn ($item) => $item->id === $latestUser->id);
            });
    }

    public function test_admin_can_view_single_user_transaction_history_without_cross_account_leakage(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $targetUser = User::query()->where('email', 'user@example.com')->firstOrFail();
        $otherUser = User::query()->create([
            'username' => 'otheruser',
            'name' => 'Other User',
            'email' => 'otheruser@example.com',
            'phone' => '0989999999',
            'password' => 'password',
            'role' => 'user',
        ]);

        TransactionLog::query()->create([
            'user_id' => $targetUser->id,
            'transaction_type' => TransactionLog::TYPE_AFFILIATE,
            'amount' => 450000,
            'description' => 'Hoa hồng user mục tiêu',
            'notes' => 'Chỉ thuộc user mục tiêu',
            'status' => TransactionLog::STATUS_SUCCESS,
            'reference_id' => 'TXN-TARGET-1',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        TransactionLog::query()->create([
            'user_id' => $otherUser->id,
            'transaction_type' => TransactionLog::TYPE_POOL_SHARE,
            'amount' => 999999,
            'description' => 'Giao dịch user khác',
            'notes' => 'Không được lẫn vào user mục tiêu',
            'status' => TransactionLog::STATUS_SUCCESS,
            'reference_id' => 'TXN-OTHER-1',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $targetUser));

        $response
            ->assertOk()
            ->assertSeeText($targetUser->username)
            ->assertSeeText('Hoa hồng user mục tiêu')
            ->assertDontSeeText('Giao dịch user khác')
            ->assertViewHas('transactions', function ($paginator) use ($targetUser) {
                return $paginator->total() >= 1
                    && collect($paginator->items())->every(fn ($transaction) => $transaction->user_id === $targetUser->id);
            });
    }

    public function test_admin_can_search_users_by_email_or_username(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $alphaUser = User::query()->create([
            'username' => 'alphauser',
            'name' => 'Alpha User',
            'email' => 'alpha@example.com',
            'phone' => '0971111111',
            'password' => 'password',
            'role' => 'user',
        ]);

        $betaUser = User::query()->create([
            'username' => 'betatest',
            'name' => 'Beta User',
            'email' => 'beta@example.com',
            'phone' => '0972222222',
            'password' => 'password',
            'role' => 'user',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'alpha@example.com']))
            ->assertOk()
            ->assertSeeText('alpha@example.com')
            ->assertDontSeeText('beta@example.com');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'betatest']))
            ->assertOk()
            ->assertSeeText($betaUser->username)
            ->assertDontSeeText($alphaUser->username);
    }

    public function test_non_admin_cannot_access_admin_user_management_routes(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.users.show', $user))
            ->assertForbidden();
    }
}
