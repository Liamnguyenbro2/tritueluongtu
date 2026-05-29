<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('bank_qr_enabled')->default(true)->after('features');
            $table->boolean('wallet_enabled')->default(true)->after('bank_qr_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['bank_qr_enabled', 'wallet_enabled']);
        });
    }
};
