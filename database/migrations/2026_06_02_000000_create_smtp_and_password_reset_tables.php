<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('smtp_settings')) {
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
        }

        if (! Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->id();
                $table->string('template_key')->unique();
                $table->string('subject');
                $table->longText('content');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('password_reset_otps')) {
            Schema::create('password_reset_otps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('email', 191);
                $table->string('otp_hash');
                $table->string('ip_address', 45);
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->timestamps();

                $table->index(['email', 'created_at'], 'password_reset_otps_email_created_idx');
                $table->index(['ip_address', 'created_at'], 'password_reset_otps_ip_created_idx');
                $table->index(['user_id', 'used_at'], 'password_reset_otps_user_used_idx');
            });
        }

        if (! DB::table('email_templates')->where('template_key', 'forgot_password')->exists()) {
            DB::table('email_templates')->insert([
                'template_key' => 'forgot_password',
                'subject' => html_entity_decode('Kh&#244;i ph&#7909;c m&#7853;t kh&#7849;u - {{site_name}}'),
                'content' => implode("\n", [
                    html_entity_decode('Xin ch&#224;o,'),
                    '',
                    html_entity_decode('Ch&#250;ng t&#244;i nh&#7853;n &#273;&#432;&#7907;c y&#234;u c&#7847;u &#273;&#7863;t l&#7841;i m&#7853;t kh&#7849;u cho t&#224;i kho&#7843;n c&#7911;a b&#7841;n.'),
                    html_entity_decode('M&#227; OTP c&#7911;a b&#7841;n l&#224;:'),
                    '{{otp}}',
                    '',
                    html_entity_decode('M&#227; c&#243; hi&#7879;u l&#7921;c trong {{expire_minutes}} ph&#250;t.'),
                    html_entity_decode('N&#7871;u b&#7841;n kh&#244;ng th&#7921;c hi&#7879;n y&#234;u c&#7847;u n&#224;y, vui l&#242;ng b&#7887; qua email n&#224;y.'),
                    '',
                    html_entity_decode('Tr&#226;n tr&#7885;ng,'),
                    '{{site_name}}',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('smtp_settings');
    }
};
