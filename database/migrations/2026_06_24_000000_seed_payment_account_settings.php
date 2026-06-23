<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            'payment_bank_name' => 'MBBANK',
            'payment_bank_code' => 'MBBANK',
            'payment_account_no' => '9969279668',
            'payment_account_name' => 'CTY TNHH KET NOI TRI TUE LUONG TU',
        ];

        foreach ($settings as $key => $value) {
            DB::table('site_settings')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('site_settings')->whereIn('key', [
            'payment_bank_name',
            'payment_bank_code',
            'payment_account_no',
            'payment_account_name',
        ])->delete();
    }
};
