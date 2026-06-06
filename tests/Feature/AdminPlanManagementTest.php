<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_plan_pricing_copy_payment_methods_and_qr_image(): void
    {
        $this->seed();
        Storage::fake('public');

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.plans.show', $plan))
            ->assertOk()
            ->assertSee($plan->name)
            ->assertSee('QR')
            ->assertSee('wallet_enabled', false)
            ->assertSee('bank_qr_enabled', false);

        $this->actingAs($admin)
            ->put(route('admin.plans.update', $plan), [
                'name' => 'Goi Nang Cap 30 Ngay',
                'description' => 'Mo ta moi cho goi nang cap theo thang.',
                'duration_days' => 45,
                'price_vnd' => '299.000',
                'features_text' => "Bat noi dung premium\nHo tro kich hoat nhanh\nLuu lich su giao dich",
                'bank_qr_enabled' => '1',
                'bank_qr_image' => $this->fakePngUpload('monthly-qr.png'),
                'wallet_enabled' => '0',
            ])
            ->assertRedirect(route('admin.plans.show', $plan));

        $plan->refresh();

        $this->assertSame('Goi Nang Cap 30 Ngay', $plan->name);
        $this->assertSame('Mo ta moi cho goi nang cap theo thang.', $plan->description);
        $this->assertSame(45, $plan->duration_days);
        $this->assertSame(299000, $plan->price_vnd);
        $this->assertSame([
            'Bat noi dung premium',
            'Ho tro kich hoat nhanh',
            'Luu lich su giao dich',
        ], $plan->features);
        $this->assertTrue($plan->bank_qr_enabled);
        $this->assertFalse($plan->wallet_enabled);
        $this->assertNotNull($plan->bank_qr_image_path);
        Storage::disk('public')->assertExists($plan->bank_qr_image_path);

        $this->actingAs($admin)
            ->get($plan->bankQrImageUrl())
            ->assertOk();

        $this->actingAs($admin)
            ->get($plan->bankQrImageDownloadUrl())
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="plan-qr-monthly.png"');
    }

    public function test_billing_page_uses_plan_copy_and_renders_qr_image_when_enabled(): void
    {
        $this->seed();
        Storage::fake('public');

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->orderBy('position')->firstOrFail();

        Storage::disk('public')->put('plan-qr/monthly.png', 'fake-image-content');

        $plan->update([
            'description' => 'Noi dung mo ta moi tren trang billing.',
            'features' => [
                'Tinh nang A',
                'Tinh nang B',
            ],
            'bank_qr_enabled' => true,
            'bank_qr_image_path' => 'plan-qr/monthly.png',
            'wallet_enabled' => false,
        ]);

        $this->actingAs($user)
            ->get(route('billing'))
            ->assertOk()
            ->assertSee('N&#226;ng c&#7845;p g&#243;i', false)
            ->assertSee('S&#7889; d&#432; v&#237;', false)
            ->assertSee('L&#7883;ch s&#7917; h&#243;a &#273;&#417;n thanh to&#225;n g&#7847;n &#273;&#226;y', false)
            ->assertSee('Noi dung mo ta moi tren trang billing.')
            ->assertSee('Tinh nang A')
            ->assertSee('Tinh nang B')
            ->assertSee('Ch&#7885;n b&#224;i h&#7885;c m&#7903; tr&#7921;c ti&#7871;p', false)
            ->assertSee($lesson->title)
            ->assertSee($plan->bankQrImageUrl(), false)
            ->assertSee('data-save-image-url="'.$plan->bankQrImageUrl().'"', false)
            ->assertSee('data-save-image-name="'.$plan->bankQrImageFileName().'"', false)
            ->assertSee('T&#7843;i m&#227; QR v&#7873; m&#225;y', false)
            ->assertSee('value="bank_qr"', false);
    }

    public function test_billing_page_blocks_disabled_payment_methods(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'user@example.com')->firstOrFail();
        Plan::query()->update([
            'bank_qr_enabled' => false,
            'wallet_enabled' => false,
        ]);

        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $lesson = Lesson::query()->where('is_trial', false)->orderBy('position')->firstOrFail();

        $plan->update([
            'description' => 'Noi dung mo ta moi tren trang billing.',
            'features' => [
                'Tinh nang A',
                'Tinh nang B',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('billing'))
            ->assertOk()
            ->assertSee('G&#243;i n&#224;y &#273;ang t&#7841;m t&#7855;t c&#7843; hai ph&#432;&#417;ng th&#7913;c thanh to&#225;n.', false)
            ->assertDontSee('value="bank_qr"', false)
            ->assertDontSee('value="wallet"', false);

        $this->actingAs($user)
            ->from(route('billing'))
            ->post(route('billing.orders.store'), [
                'plan_id' => $plan->id,
                'lesson_id' => $lesson->id,
                'payment_method' => 'bank_qr',
            ])
            ->assertRedirect(route('billing'))
            ->assertSessionHasErrors('payment_method');
    }

    private function fakePngUpload(string $filename): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'qr-test-');

        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9p0z2uoAAAAASUVORK5CYII='
        ));

        return new UploadedFile($path, $filename, 'image/png', null, true);
    }
}
