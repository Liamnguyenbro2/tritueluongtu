<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gmail_address');
            $table->text('app_password_encrypted')->nullable();
            $table->string('smtp_host')->default('smtp.gmail.com');
            $table->unsignedSmallInteger('smtp_port')->default(587);
            $table->string('encryption', 20)->default('tls');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key')->unique();
            $table->string('subject');
            $table->longText('content');
            $table->timestamps();
        });

        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('otp_hash');
            $table->string('ip_address', 45);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'created_at'], 'password_reset_otps_email_created_idx');
            $table->index(['ip_address', 'created_at'], 'password_reset_otps_ip_created_idx');
            $table->index(['user_id', 'used_at'], 'password_reset_otps_user_used_idx');
        });

        DB::table('email_templates')->insert([
            'template_key' => 'forgot_password',
            'subject' => 'Khôi phục mật khẩu - {{site_name}}',
            'content' => implode("\n", [
                'Xin chào,',
                '',
                'Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.',
                'Mã OTP của bạn là:',
                '{{otp}}',
                '',
                'Mã có hiệu lực trong {{expire_minutes}} phút.',
                'Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.',
                '',
                'Trân trọng,',
                '{{site_name}}',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('smtp_settings');
    }
};
