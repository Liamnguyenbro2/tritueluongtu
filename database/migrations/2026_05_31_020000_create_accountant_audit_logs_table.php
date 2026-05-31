<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accountant_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 80);
            $table->string('target_type', 80)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('description');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['actor_user_id', 'created_at'], 'acct_audit_actor_idx');
            $table->index(['target_type', 'target_id'], 'acct_audit_target_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accountant_audit_logs');
    }
};
