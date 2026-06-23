<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropPlanForeignKeyIfExists();

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->change();
            $table->string('order_type', 40)->nullable()->after('code');
            $table->unsignedBigInteger('item_id')->nullable()->after('order_type');
            $table->timestamp('expires_at')->nullable()->after('paid_at');
            $table->index(['order_type', 'status'], 'payment_orders_type_status_idx');
            $table->foreign('plan_id')->references('id')->on('plans');
        });
    }

    public function down(): void
    {
        $this->dropPlanForeignKeyIfExists();

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropIndex('payment_orders_type_status_idx');
            $table->dropColumn(['order_type', 'item_id', 'expires_at']);
        });

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable(false)->change();
            $table->foreign('plan_id')->references('id')->on('plans');
        });
    }

    private function dropPlanForeignKeyIfExists(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->dropForeign(['plan_id']);
            });

            return;
        }

        $foreignKey = DB::selectOne(<<<'SQL'
            SELECT CONSTRAINT_NAME AS constraint_name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'payment_orders'
              AND COLUMN_NAME = 'plan_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        SQL);

        if (! $foreignKey?->constraint_name) {
            return;
        }

        $constraintName = str_replace('`', '``', $foreignKey->constraint_name);
        DB::statement("ALTER TABLE `payment_orders` DROP FOREIGN KEY `{$constraintName}`");
    }
};
