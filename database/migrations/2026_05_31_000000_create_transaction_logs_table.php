<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_type', 40);
            $table->bigInteger('amount');
            $table->text('description');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('success');
            $table->string('reference_id', 120)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'txn_logs_user_created_idx');
            $table->index(['user_id', 'transaction_type'], 'txn_logs_user_type_idx');
            $table->index(['user_id', 'status'], 'txn_logs_user_status_idx');
            $table->index('reference_id', 'txn_logs_ref_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
    }
};
