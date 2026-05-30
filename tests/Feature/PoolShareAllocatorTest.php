<?php

namespace Tests\Feature;

use App\Services\PoolShareAllocator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PoolShareAllocatorTest extends TestCase
{
    public function test_allocator_uses_new_percentages_and_distributes_full_pool_total(): void
    {
        $allocation = app(PoolShareAllocator::class)->allocate(
            20000000,
            collect([
                'A' => $this->fakeRecipients('A', 30),
                'B' => $this->fakeRecipients('B', 10),
                'C' => $this->fakeRecipients('C', 5),
            ])
        );

        $summary = collect($allocation['summary']);
        $payouts = collect($allocation['payouts']);

        $this->assertSame(6660000, $summary['A']['paid_total']);
        $this->assertSame(222000, $summary['A']['amount_each']);
        $this->assertSame(6660000, $summary['B']['paid_total']);
        $this->assertSame(666000, $summary['B']['amount_each']);
        $this->assertSame(6680000, $summary['C']['paid_total']);
        $this->assertSame(1336000, $summary['C']['amount_each']);
        $this->assertSame(20000000, $allocation['paid_total']);
        $this->assertSame(0, $allocation['retained_total']);
        $this->assertSame(20000000, (int) $payouts->sum('amount_vnd'));
    }

    public function test_allocator_keeps_unqualified_group_funds_in_shared_pool_balance(): void
    {
        $allocation = app(PoolShareAllocator::class)->allocate(
            450000,
            collect([
                'A' => $this->fakeRecipients('A', 1),
                'B' => collect(),
                'C' => collect(),
            ])
        );

        $summary = collect($allocation['summary']);
        $payouts = collect($allocation['payouts']);

        $this->assertSame(149850, $summary['A']['paid_total']);
        $this->assertSame(149850, $summary['A']['amount_each']);
        $this->assertSame(149850, $summary['B']['retained_total']);
        $this->assertSame(150300, $summary['C']['retained_total']);
        $this->assertSame(149850, $allocation['paid_total']);
        $this->assertSame(300150, $allocation['retained_total']);
        $this->assertSame(149850, (int) $payouts->sum('amount_vnd'));
    }

    private function fakeRecipients(string $group, int $count): Collection
    {
        return collect(range(1, $count))->map(fn (int $index) => [
            'id' => $index,
            'name' => "{$group} User {$index}",
            'email' => strtolower($group)."-{$index}@example.com",
            'active_referrals_count' => 0,
            'subscription_ends_at' => null,
        ]);
    }
}
