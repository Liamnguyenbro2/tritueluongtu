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
        });

        if (! Schema::hasColumn('payment_orders', 'order_type')) {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->string('order_type', 40)->nullable()->after('code');
            });
        }

        if (! Schema::hasColumn('payment_orders', 'item_id')) {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('item_id')->nullable()->after('order_type');
            });
        }

        if (! Schema::hasColumn('payment_orders', 'expires_at')) {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('paid_at');
            });
        }

        $this->addPlanForeignKeyIfMissing();
    }

    public function down(): void
    {
        $this->dropPlanForeignKeyIfExists();

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropColumn(['order_type', 'item_id', 'expires_at']);
        });

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable(false)->change();
            $table->foreign('plan_id')->references('id')->on('plans');
        });
    }

    private function addPlanForeignKeyIfMissing(): void
    {
        if (DB::getDriverName() === 'mysql' && $this->mysqlPlanForeignKeyName() !== null) {
            return;
        }

        Schema::table('payment_orders', function (Blueprint $table) {
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

        $constraintName = $this->mysqlPlanForeignKeyName();

        if (! $constraintName) {
            return;
        }

        $escapedConstraintName = str_replace('`', '``', $constraintName);
        DB::statement("ALTER TABLE `payment_orders` DROP FOREIGN KEY `{$escapedConstraintName}`");
    }

    private function mysqlPlanForeignKeyName(): ?string
    {
        $foreignKey = DB::selectOne(<<<'SQL'
            SELECT CONSTRAINT_NAME AS constraint_name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'payment_orders'
              AND COLUMN_NAME = 'plan_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        SQL);

        return $foreignKey?->constraint_name ?: null;
    }
};
