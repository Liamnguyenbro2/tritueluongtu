<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscriptions') && ! Schema::hasColumn('subscriptions', 'grants_full_library')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->boolean('grants_full_library')->default(true)->after('status');
            });
        }

        if (Schema::hasTable('lessons') && ! Schema::hasColumn('lessons', 'unlock_price_vnd')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->unsignedBigInteger('unlock_price_vnd')->default(49000)->after('duration_minutes');
            });
        }

        if (! Schema::hasTable('lesson_unlocks')) {
            Schema::create('lesson_unlocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
                $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('amount_vnd')->default(0);
                $table->timestamp('unlocked_at');
                $table->timestamps();
                $table->unique(['user_id', 'lesson_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_unlocks');

        if (Schema::hasTable('lessons') && Schema::hasColumn('lessons', 'unlock_price_vnd')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->dropColumn('unlock_price_vnd');
            });
        }

        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'grants_full_library')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('grants_full_library');
            });
        }
    }
};
