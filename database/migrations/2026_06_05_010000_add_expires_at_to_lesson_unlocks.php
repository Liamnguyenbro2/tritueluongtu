<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lesson_unlocks')) {
            return;
        }

        if (! Schema::hasColumn('lesson_unlocks', 'expires_at')) {
            Schema::table('lesson_unlocks', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('unlocked_at');
            });
        }

        $rows = DB::table('lesson_unlocks')
            ->leftJoin('subscriptions', 'subscriptions.id', '=', 'lesson_unlocks.subscription_id')
            ->select([
                'lesson_unlocks.id',
                'lesson_unlocks.unlocked_at',
                'lesson_unlocks.expires_at',
                'subscriptions.ends_at as subscription_ends_at',
            ])
            ->orderBy('lesson_unlocks.id')
            ->get();

        foreach ($rows as $row) {
            if ($row->expires_at) {
                continue;
            }

            $expiresAt = $row->subscription_ends_at
                ? Carbon::parse($row->subscription_ends_at)
                : Carbon::parse($row->unlocked_at)->addDays(30);

            DB::table('lesson_unlocks')
                ->where('id', $row->id)
                ->update([
                    'expires_at' => $expiresAt,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('lesson_unlocks') || ! Schema::hasColumn('lesson_unlocks', 'expires_at')) {
            return;
        }

        Schema::table('lesson_unlocks', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
