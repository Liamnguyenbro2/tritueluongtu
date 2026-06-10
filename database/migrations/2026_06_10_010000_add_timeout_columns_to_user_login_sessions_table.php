<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_login_sessions', function (Blueprint $table) {
            $table->timestamp('login_at')->nullable()->after('ip_address');
            $table->timestamp('idle_expires_at')->nullable()->after('last_seen_at')->index();
            $table->timestamp('absolute_expires_at')->nullable()->after('idle_expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('user_login_sessions', function (Blueprint $table) {
            $table->dropColumn(['login_at', 'idle_expires_at', 'absolute_expires_at']);
        });
    }
};
