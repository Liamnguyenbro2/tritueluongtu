<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('voice_sample_path')->nullable()->after('accepted_terms_at');
            $table->timestamp('voice_sample_uploaded_at')->nullable()->after('voice_sample_path');
            $table->timestamp('voice_sample_delete_after_at')->nullable()->after('voice_sample_uploaded_at');
            $table->timestamp('voice_sample_completed_at')->nullable()->after('voice_sample_delete_after_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'voice_sample_path',
                'voice_sample_uploaded_at',
                'voice_sample_delete_after_at',
                'voice_sample_completed_at',
            ]);
        });
    }
};
