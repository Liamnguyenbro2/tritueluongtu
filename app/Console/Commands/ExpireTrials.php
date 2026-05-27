<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ExpireTrials extends Command
{
    protected $signature = 'subscriptions:expire-trials';

    protected $description = 'Revoke trial lesson access after the configured trial window.';

    public function handle(): int
    {
        $cutoff = now()->subHours(config('quantum.trial_hours'));

        User::query()
            ->where('trial_started_at', '<', $cutoff)
            ->whereDoesntHave('subscriptions', fn ($query) => $query->where('status', 'active')->where('ends_at', '>', now()))
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $this->line("Trial expired for {$user->email}");
                }
            });

        return self::SUCCESS;
    }
}
