<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_non_existent_route_returns_styled_404_page(): void
    {
        $response = $this->get('/non-existent-route-for-testing');

        $response->assertStatus(404)
            ->assertSee('HTTP 404')
            ->assertSee('Page Not Found')
            ->assertSee('Return to Home');
    }

    public function test_error_views_render_with_expected_status_codes(): void
    {
        $this->view('errors.403')
            ->assertSee('HTTP 403')
            ->assertSee('Access Forbidden');

        $this->view('errors.404')
            ->assertSee('HTTP 404')
            ->assertSee('Page Not Found');

        $this->view('errors.419')
            ->assertSee('HTTP 419')
            ->assertSee('Page Expired');

        $this->view('errors.429')
            ->assertSee('HTTP 429')
            ->assertSee('Too Many Requests');

        $this->view('errors.500')
            ->assertSee('HTTP 500')
            ->assertSee('Internal Server Error');
    }
}
