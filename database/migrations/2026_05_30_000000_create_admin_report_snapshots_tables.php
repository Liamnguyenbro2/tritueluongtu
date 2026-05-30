<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const POOL_SHARE_ROWS_GROUP_INDEX = 'ars_pool_rows_sid_grp_idx';

    public function up(): void
    {
        if (! Schema::hasTable('admin_report_snapshots')) {
            Schema::create('admin_report_snapshots', function (Blueprint $table) {
                $table->id();
                $table->date('report_date')->unique();
                $table->timestamp('captured_at');
                $table->unsignedInteger('new_paid_members_count')->default(0);
                $table->unsignedInteger('activation_count')->default(0);
                $table->unsignedBigInteger('gross_sales_vnd')->default(0);
                $table->unsignedBigInteger('affiliate_commission_vnd')->default(0);
                $table->unsignedBigInteger('vat_vnd')->default(0);
                $table->unsignedBigInteger('company_revenue_vnd')->default(0);
                $table->unsignedBigInteger('pool_share_in_vnd')->default(0);
                $table->unsignedBigInteger('pool_share_distributed_vnd')->default(0);
                $table->unsignedBigInteger('shared_pool_balance_vnd')->default(0);
                $table->json('pool_group_stats')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('admin_report_snapshot_logs')) {
            Schema::create('admin_report_snapshot_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('snapshot_id')->constrained('admin_report_snapshots')->cascadeOnDelete();
                $table->unsignedBigInteger('ledger_entry_id')->nullable();
                $table->string('log_type', 80)->index();
                $table->bigInteger('amount_vnd')->default(0);
                $table->text('memo')->nullable();
                $table->timestamp('occurred_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('admin_report_snapshot_pool_share_rows')) {
            Schema::create('admin_report_snapshot_pool_share_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('snapshot_id')->constrained('admin_report_snapshots')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('group_code', 12);
                $table->unsignedInteger('active_referrals_count')->default(0);
                $table->unsignedBigInteger('payout_vnd')->default(0);
                $table->string('account_status', 60);
                $table->timestamp('subscription_ends_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('admin_report_snapshot_pool_share_rows') && ! $this->indexExists('admin_report_snapshot_pool_share_rows', self::POOL_SHARE_ROWS_GROUP_INDEX)) {
            Schema::table('admin_report_snapshot_pool_share_rows', function (Blueprint $table) {
                $table->index(['snapshot_id', 'group_code'], self::POOL_SHARE_ROWS_GROUP_INDEX);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_report_snapshot_pool_share_rows');
        Schema::dropIfExists('admin_report_snapshot_logs');
        Schema::dropIfExists('admin_report_snapshots');
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $database = DB::getDatabaseName();
            $result = DB::selectOne(
                'select count(*) as aggregate from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ?',
                [$database, $table, $indexName]
            );

            return (int) ($result->aggregate ?? 0) > 0;
        }

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
};
