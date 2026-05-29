<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_uses_the_new_quantum_style_landing_page(): void
    {
        $this->seed();

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Kích hoạt')
            ->assertSee('sóng lượng tử')
            ->assertSee('Trải nghiệm giúp bạn')
            ->assertSee('Cảm nhận từ người dùng');
    }
}
