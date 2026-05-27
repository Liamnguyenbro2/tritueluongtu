<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable()->after('description');
            $table->string('media_type')->nullable()->after('thumbnail_path');
            $table->string('media_path')->nullable()->after('media_type');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_path', 'media_type', 'media_path']);
        });
    }
};
