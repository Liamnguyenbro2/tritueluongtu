<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('pit_rate_percent')->default(0)->after('amount_vnd');
            $table->unsignedBigInteger('pit_amount_vnd')->default(0)->after('pit_rate_percent');
            $table->unsignedBigInteger('net_amount_vnd')->nullable()->after('pit_amount_vnd');
        });

        // Existing withdrawals keep their original payout and are not taxed retroactively.
        DB::table('withdrawal_requests')
            ->whereNull('net_amount_vnd')
            ->update(['net_amount_vnd' => DB::raw('amount_vnd')]);
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropColumn(['pit_rate_percent', 'pit_amount_vnd', 'net_amount_vnd']);
        });
    }
};
