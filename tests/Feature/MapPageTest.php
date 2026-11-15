<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('preview-map')
            ->assertSee('Power Outage Map')
            ->assertSee('Live Map')
            ->assertSee(route('map'));
    }

    public function test_public_map_page_can_be_rendered(): void
    {
        $response = $this->get('/map');

        $response->assertStatus(200)
            ->assertSee('outage-map')
            ->assertSee('Power Outage Map')
            ->assertSee('Map Legend');
    }

    public function test_authenticated_user_can_view_landing_and_map_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertStatus(200)
            ->assertSee('My Reports')
            ->assertSee('preview-map');

        $this->actingAs($user)
            ->get('/map')
            ->assertStatus(200)
            ->assertSee('outage-map')
            ->assertSee('My Reports');
    }
}
