<?php

namespace Tests\Feature;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Models\Outage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OutageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_active_outages_within_viewport_bounding_box(): void
    {
        $user = User::factory()->create();

        $inBounds = Outage::factory()->create([
            'user_id' => $user->id,
            'latitude' => 10.48000000,
            'longitude' => -66.86000000,
            'radius_km' => 2.0,
            'min_lat' => 10.46200000,
            'max_lat' => 10.49800000,
            'min_lng' => -66.87800000,
            'max_lng' => -66.84200000,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(4),
            'description' => 'Active grid failure',
        ]);

        Outage::factory()->create([
            'user_id' => $user->id,
            'latitude' => 10.65000000,
            'longitude' => -71.65000000,
            'radius_km' => 2.0,
            'min_lat' => 10.63000000,
            'max_lat' => 10.67000000,
            'min_lng' => -71.67000000,
            'max_lng' => -71.63000000,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(4),
        ]);

        Outage::factory()->create([
            'user_id' => $user->id,
            'latitude' => 10.48500000,
            'longitude' => -66.86500000,
            'status' => OutageStatus::Expired,
            'expires_at' => now()->subHour(),
        ]);

        Outage::factory()->closed()->create([
            'user_id' => $user->id,
            'latitude' => 10.48200000,
            'longitude' => -66.86200000,
        ]);

        $response = $this->getJson('/api/v1/outages?min_lat=10.45&max_lat=10.52&min_lng=-66.90&max_lng=-66.80');

        $response->assertStatus(200)
            ->assertHeader('Cache-Control', 'max-age=15, public, stale-while-revalidate=30')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inBounds->id)
            ->assertJsonPath('data.0.status', 'active')
            ->assertJsonPath('data.0.description', 'Active grid failure')
            ->assertJsonPath('data.0.is_owner', false)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'latitude',
                        'longitude',
                        'radius_km',
                        'bounding_box' => ['min_lat', 'max_lat', 'min_lng', 'max_lng'],
                        'status',
                        'duration_type',
                        'duration_hours',
                        'expires_at',
                        'closed_at',
                        'created_at',
                        'description',
                        'is_owner',
                    ],
                ],
            ]);

        $data = $response->json('data.0');
        $this->assertArrayNotHasKey('user_id', $data);
        $this->assertArrayNotHasKey('user', $data);
        $this->assertArrayNotHasKey('email', $data);
    }

    public function test_viewport_validation_requires_valid_geographic_coordinates(): void
    {
        $this->getJson('/api/v1/outages')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['min_lat', 'max_lat', 'min_lng', 'max_lng']);

        $this->getJson('/api/v1/outages?min_lat=-95&max_lat=95&min_lng=-60&max_lng=-50')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['min_lat', 'max_lat']);

        $this->getJson('/api/v1/outages?min_lat=20&max_lat=10&min_lng=-60&max_lng=-50')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['max_lat']);
    }

    public function test_guest_cannot_create_or_close_outage(): void
    {
        $outage = Outage::factory()->create();

        $this->postJson('/api/v1/outages', [
            'latitude' => 10.48,
            'longitude' => -66.86,
            'radius_km' => 2.0,
            'duration_type' => 'fixed',
            'duration_hours' => 5,
        ])->assertStatus(401);

        $this->postJson("/api/v1/outages/{$outage->id}/close")
            ->assertStatus(401);
    }

    public function test_authenticated_user_can_create_outage_with_fixed_duration(): void
    {
        Carbon::setTestNow('2026-11-14 12:00:00');

        $user = User::factory()->create();

        $payload = [
            'latitude' => 10.48000000,
            'longitude' => -66.86000000,
            'radius_km' => 2.50,
            'duration_type' => 'fixed',
            'duration_hours' => 6,
            'description' => 'Feeder tripped due to overload.',
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/outages', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.duration_type', 'fixed')
            ->assertJsonPath('data.duration_hours', 6)
            ->assertJsonPath('data.is_owner', true)
            ->assertJsonPath('data.description', 'Feeder tripped due to overload.');

        $this->assertDatabaseHas('outages', [
            'user_id' => $user->id,
            'latitude' => 10.48000000,
            'longitude' => -66.86000000,
            'radius_km' => 2.50,
            'duration_type' => DurationType::Fixed->value,
            'duration_hours' => 6,
            'status' => OutageStatus::Active->value,
            'expires_at' => '2026-11-14 18:00:00',
        ]);

        $created = Outage::where('user_id', $user->id)->first();
        $this->assertNotNull($created->min_lat);
        $this->assertNotNull($created->max_lat);
        $this->assertLessThan($created->latitude, $created->min_lat);
        $this->assertGreaterThan($created->latitude, $created->max_lat);

        Carbon::setTestNow();
    }

    public function test_authenticated_user_can_create_outage_with_until_closed_duration(): void
    {
        Carbon::setTestNow('2026-11-14 12:00:00');

        $user = User::factory()->create();

        $payload = [
            'latitude' => 10.48000000,
            'longitude' => -66.86000000,
            'radius_km' => 1.50,
            'duration_type' => 'until_closed',
            'description' => 'Waiting for crew inspection.',
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/outages', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.duration_type', 'until_closed')
            ->assertJsonPath('data.duration_hours', null);

        // Safety cap: expires_at set to now() + 72h
        $this->assertDatabaseHas('outages', [
            'user_id' => $user->id,
            'duration_type' => DurationType::UntilClosed->value,
            'duration_hours' => null,
            'status' => OutageStatus::Active->value,
            'expires_at' => '2026-11-17 12:00:00',
        ]);

        Carbon::setTestNow();
    }

    public function test_create_outage_validation_fails_for_invalid_radius_or_coordinates(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/outages', [
            'latitude' => 10.48,
            'longitude' => -66.86,
            'radius_km' => 0.2,
            'duration_type' => 'fixed',
            'duration_hours' => 4,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['radius_km']);

        $this->actingAs($user)->postJson('/api/v1/outages', [
            'latitude' => 10.48,
            'longitude' => -66.86,
            'radius_km' => 30.0,
            'duration_type' => 'fixed',
            'duration_hours' => 4,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['radius_km']);

        $this->actingAs($user)->postJson('/api/v1/outages', [
            'latitude' => 10.48,
            'longitude' => -66.86,
            'radius_km' => 2.0,
            'duration_type' => 'fixed',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['duration_hours']);
    }

    public function test_user_cannot_exceed_max_active_reports_limit(): void
    {
        $user = User::factory()->create();

        Outage::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(5),
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/outages', [
            'latitude' => 10.48,
            'longitude' => -66.86,
            'radius_km' => 1.5,
            'duration_type' => 'fixed',
            'duration_hours' => 2,
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'You have reached the maximum limit of 3 active reports.',
            ]);
    }

    public function test_owner_can_manually_close_active_outage(): void
    {
        $user = User::factory()->create();
        $outage = Outage::factory()->create([
            'user_id' => $user->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(6),
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/outages/{$outage->id}/close");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.id', $outage->id);

        $this->assertDatabaseHas('outages', [
            'id' => $outage->id,
            'status' => OutageStatus::Closed->value,
        ]);

        $this->assertNotNull($outage->fresh()->closed_at);
    }

    public function test_non_owner_cannot_close_another_users_outage(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $outage = Outage::factory()->create([
            'user_id' => $owner->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(4),
        ]);

        $response = $this->actingAs($stranger)->postJson("/api/v1/outages/{$outage->id}/close");

        $response->assertStatus(403);

        $this->assertDatabaseHas('outages', [
            'id' => $outage->id,
            'status' => OutageStatus::Active->value,
        ]);
    }

    public function test_cannot_close_already_closed_or_expired_outage(): void
    {
        $user = User::factory()->create();

        $closedOutage = Outage::factory()->closed()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->postJson("/api/v1/outages/{$closedOutage->id}/close")
            ->assertStatus(422)
            ->assertJsonPath('message', 'This report is no longer active.');
    }

    public function test_expire_outages_command_transitions_overdue_records(): void
    {
        $user = User::factory()->create();

        $overdue = Outage::factory()->create([
            'user_id' => $user->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->subMinute(),
        ]);

        $future = Outage::factory()->create([
            'user_id' => $user->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHour(),
        ]);

        $closed = Outage::factory()->closed()->create([
            'user_id' => $user->id,
            'expires_at' => now()->subHour(),
        ]);

        $this->artisan('outages:expire')
            ->expectsOutput('Expired 1 overdue outage reports.')
            ->assertSuccessful();

        $this->assertSame(OutageStatus::Expired, $overdue->fresh()->status);
        $this->assertSame(OutageStatus::Active, $future->fresh()->status);
        $this->assertSame(OutageStatus::Closed, $closed->fresh()->status);
    }
}
