<?php

namespace App\Console\Commands;

use App\Models\ReferralLink;
use App\Models\User;
use App\Services\WalletLedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetProductionData extends Command
{
    protected $signature = 'system:reset-production-data {--force : Run without confirmation prompt}';

    protected $description = 'Reset customer accounts and financial data while keeping admin/accountant users, plans, lessons, and site settings.';

    /**
     * @var array<int, string>
     */
    private array $tablesToTruncate = [
        'account_suspensions',
        'announcement_reads',
        'password_reset_otps',
        'transaction_logs',
        'accountant_audit_logs',
        'withdrawal_requests',
        'bank_webhook_events',
        'sepay_webhook_logs',
        'payment_transactions',
        'payment_orders',
        'ledger_entries',
        'wallets',
        'subscriptions',
        'user_lesson_access',
        'referrals',
        'referral_links',
        'bank_accounts',
        'admin_report_snapshot_pool_share_rows',
        'admin_report_snapshot_logs',
        'admin_report_snapshots',
    ];

    public function handle(WalletLedgerService $wallets): int
    {
        if (! $this->option('force') && ! $this->confirm('This will delete customer accounts and financial data. Continue?')) {
            $this->warn('Reset cancelled.');

            return self::INVALID;
        }

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($wallets): void {
                foreach ($this->tablesToTruncate as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->truncate();
                    }
                }

                $keptUserIds = User::query()
                    ->where('is_admin', true)
                    ->orWhere('role', 'accountant')
                    ->pluck('id');

                if (Schema::hasTable('user_profiles')) {
                    DB::table('user_profiles')
                        ->whereNotIn('user_id', $keptUserIds)
                        ->delete();
                }

                User::query()
                    ->where('is_admin', false)
                    ->where(function ($query) {
                        $query->whereNull('role')
                            ->orWhere('role', '!=', 'accountant');
                    })
                    ->delete();

                $wallets->ensureSystemWallets();

                User::query()
                    ->where('is_admin', true)
                    ->orWhere('role', 'accountant')
                    ->get()
                    ->each(function (User $user) use ($wallets): void {
                        $user->profile()->updateOrCreate([], [
                            'accepted_terms' => true,
                            'accepted_terms_at' => now(),
                        ]);

                        ReferralLink::query()->updateOrCreate(
                            ['user_id' => $user->id],
                            ['code' => $this->buildReferralCode($user)]
                        );

                        $wallets->walletForUser($user);
                    });
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $remainingCustomers = User::query()
            ->where('is_admin', false)
            ->where(function ($query) {
                $query->whereNull('role')
                    ->orWhere('role', '!=', 'accountant');
            })
            ->count();

        $this->info('Production data reset completed.');
        $this->line('Remaining customer accounts: '.$remainingCustomers);
        $this->line('Admin/accountant accounts preserved: '.User::query()->where('is_admin', true)->orWhere('role', 'accountant')->count());

        return self::SUCCESS;
    }

    private function buildReferralCode(User $user): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($user->username ?: 'USER')), 0, 12));

        return $base.$user->id;
    }
}
