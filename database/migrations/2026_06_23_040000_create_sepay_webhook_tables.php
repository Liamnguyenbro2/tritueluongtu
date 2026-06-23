<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepay_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('webhook_uuid')->unique();
            $table->json('headers');
            $table->json('payload');
            $table->string('ip_address', 45)->nullable();
            $table->string('status', 40)->default('received');
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 50);
            $table->string('gateway_transaction_id', 191)->nullable()->unique();
            $table->string('order_code', 191)->nullable();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('transaction_type', 100)->nullable();
            $table->string('status', 50)->default('received');
            $table->json('raw_payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['gateway', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('sepay_webhook_logs');
    }
};
