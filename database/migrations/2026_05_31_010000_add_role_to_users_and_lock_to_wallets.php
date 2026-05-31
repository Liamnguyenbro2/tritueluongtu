<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('user')->after('is_admin');
        });

        DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);

        Schema::table('wallets', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('balance_vnd');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
