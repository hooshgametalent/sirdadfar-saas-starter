<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_home_page_renders_in_persian_rtl(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('lang="fa"', false)
            ->assertSee('dir="rtl"', false);
    }
}
