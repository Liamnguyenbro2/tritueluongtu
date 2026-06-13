<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name', 190);
            $table->string('citizen_id', 32);
            $table->text('address');
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['submitted_at', 'id'], 'kyc_submitted_idx');
            $table->index('citizen_id', 'kyc_citizen_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};
