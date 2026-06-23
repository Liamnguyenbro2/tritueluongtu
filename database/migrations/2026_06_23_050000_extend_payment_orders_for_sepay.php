<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
        });

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
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropIndex('payment_orders_type_status_idx');
            $table->dropColumn(['order_type', 'item_id', 'expires_at']);
        });

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable(false)->change();
            $table->foreign('plan_id')->references('id')->on('plans');
        });
    }
};
