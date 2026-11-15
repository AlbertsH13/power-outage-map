<?php

namespace Tests\Feature;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Models\Outage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/reports/create')->assertRedirect('/login');
        $this->get('/my-reports')->assertRedirect('/login');
        $this->post('/reports', [])->assertRedirect('/login');

        $outage = Outage::factory()->create();
        $this->patch("/reports/{$outage->id}/close")->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_report_creation_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/reports/create');

        $response->assertStatus(200)
            ->assertSee('Report an Outage')
            ->assertSee('picker-map')
            ->assertSee('Fixed Hours')
            ->assertSee('Until Resolved');
    }

    public function test_authenticated_user_can_create_fixed_duration_outage(): void
    {
        Carbon::setTestNow('2026-11-15 14:00:00');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/reports', [
            'latitude' => 10.49200000,
            'longitude' => -66.88000000,
            'radius_km' => 2.50,
            'duration_type' => 'fixed',
            'duration_hours' => 6,
            'description' => 'Feeder failure affecting main residential block.',
        ]);

        $response->assertRedirect(route('reports.index'))
            ->assertSessionHas('status', 'Outage report submitted successfully.');

        $this->assertDatabaseHas('outages', [
            'user_id' => $user->id,
            'latitude' => 10.49200000,
            'longitude' => -66.88000000,
            'radius_km' => 2.50,
            'duration_type' => DurationType::Fixed->value,
            'duration_hours' => 6,
            'status' => OutageStatus::Active->value,
            'expires_at' => '2026-11-15 20:00:00',
        ]);

        $outage = Outage::where('user_id', $user->id)->first();
        $this->assertNotNull($outage->min_lat);
        $this->assertNotNull($outage->max_lat);
        $this->assertNotNull($outage->min_lng);
        $this->assertNotNull($outage->max_lng);

        Carbon::setTestNow();
    }

    public function test_authenticated_user_can_create_until_closed_outage(): void
    {
        Carbon::setTestNow('2026-11-15 14:00:00');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/reports', [
            'latitude' => 10.49200000,
            'longitude' => -66.88000000,
            'radius_km' => 1.50,
            'duration_type' => 'until_closed',
            'description' => 'Downed power line awaiting emergency crew.',
        ]);

        $response->assertRedirect(route('reports.index'))
            ->assertSessionHas('status', 'Outage report submitted successfully.');

        $this->assertDatabaseHas('outages', [
            'user_id' => $user->id,
            'duration_type' => DurationType::UntilClosed->value,
            'duration_hours' => null,
            'status' => OutageStatus::Active->value,
            'expires_at' => '2026-11-18 14:00:00',
        ]);

        Carbon::setTestNow();
    }

    public function test_user_cannot_exceed_maximum_active_reports(): void
    {
        $user = User::factory()->create();

        Outage::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(4),
        ]);

        $response = $this->actingAs($user)
            ->from('/reports/create')
            ->post('/reports', [
                'latitude' => 10.49200000,
                'longitude' => -66.88000000,
                'radius_km' => 1.00,
                'duration_type' => 'fixed',
                'duration_hours' => 2,
            ]);

        $response->assertRedirect('/reports/create')
            ->assertSessionHasErrors(['outage']);

        $this->assertDatabaseCount('outages', 3);
    }

    public function test_my_reports_displays_active_and_past_outages(): void
    {
        $user = User::factory()->create();

        $activeOutage = Outage::factory()->create([
            'user_id' => $user->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(3),
            'description' => 'Active neighborhood outage',
        ]);

        $closedOutage = Outage::factory()->closed()->create([
            'user_id' => $user->id,
            'description' => 'Resolved yesterday',
        ]);

        $response = $this->actingAs($user)->get('/my-reports');

        $response->assertStatus(200)
            ->assertSee('My Outage Reports')
            ->assertSee('Active Quota: 1 / 3')
            ->assertSee('Active neighborhood outage')
            ->assertSee('Mark as Resolved')
            ->assertSee('Resolved yesterday')
            ->assertSee('Resolved');
    }

    public function test_owner_can_manually_close_active_outage(): void
    {
        $user = User::factory()->create();
        $outage = Outage::factory()->create([
            'user_id' => $user->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(5),
        ]);

        $response = $this->actingAs($user)->patch("/reports/{$outage->id}/close");

        $response->assertRedirect()
            ->assertSessionHas('status', 'Report marked as resolved.');

        $this->assertDatabaseHas('outages', [
            'id' => $outage->id,
            'status' => OutageStatus::Closed->value,
        ]);
        $this->assertNotNull($outage->fresh()->closed_at);
    }

    public function test_non_owner_cannot_close_another_users_outage(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $outage = Outage::factory()->create([
            'user_id' => $owner->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(5),
        ]);

        $response = $this->actingAs($otherUser)->patch("/reports/{$outage->id}/close");

        $response->assertStatus(403);

        $this->assertDatabaseHas('outages', [
            'id' => $outage->id,
            'status' => OutageStatus::Active->value,
        ]);
    }
}
