<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_custom_404_page_is_rendered(): void
    {
        $this->get('/duong-dan-khong-ton-tai-123')
            ->assertNotFound()
            ->assertSee('Liên kết không tồn tại')
            ->assertSee('Liên kết không tồn tại vui lòng kiểm tra lại.')
            ->assertSee('Quay về trang chủ');
    }

    public function test_maintenance_view_contains_expected_copy(): void
    {
        $html = view('maintenance')->render();

        $this->assertStringContainsString('Website đang trong quá trình bảo trì', $html);
        $this->assertStringContainsString('Thông báo: Website đang trong quá trình bảo trì, nâng cấp định kỳ vui lòng quay lại sau.', $html);
    }
}
