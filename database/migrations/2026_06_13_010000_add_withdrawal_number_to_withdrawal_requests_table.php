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
            $table->unsignedBigInteger('withdrawal_number')->nullable()->unique()->after('id');
        });

        $rows = DB::table('withdrawal_requests')
            ->select('id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($rows as $index => $row) {
            DB::table('withdrawal_requests')
                ->where('id', $row->id)
                ->update(['withdrawal_number' => $index + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropUnique(['withdrawal_number']);
            $table->dropColumn('withdrawal_number');
        });
    }
};
