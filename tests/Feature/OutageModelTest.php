<?php

namespace Tests\Feature;

use App\Enums\OutageStatus;
use App\Models\Outage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutageModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_outage_with_factory(): void
    {
        $user = User::factory()->create();
        $outage = Outage::factory()->create([
            'user_id' => $user->id,
            'description' => 'Main Transformer Blowout',
        ]);

        $this->assertDatabaseHas('outages', [
            'id' => $outage->id,
            'user_id' => $user->id,
            'description' => 'Main Transformer Blowout',
            'status' => OutageStatus::Active->value,
        ]);
        $this->assertInstanceOf(User::class, $outage->user);
        $this->assertTrue($user->outages->contains($outage));
    }

    public function test_scope_active_filters_only_valid_active_reports(): void
    {
        $user = User::factory()->create();

        $activeOutage = Outage::factory()->create([
            'user_id' => $user->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(2),
        ]);

        $expiredByTime = Outage::factory()->create([
            'user_id' => $user->id,
            'status' => OutageStatus::Active,
            'expires_at' => now()->subMinute(),
        ]);

        $closedOutage = Outage::factory()->closed()->create([
            'user_id' => $user->id,
        ]);

        $expiredStatusOutage = Outage::factory()->expired()->create([
            'user_id' => $user->id,
        ]);

        $activeOutages = Outage::query()->active()->get();

        $this->assertTrue($activeOutages->contains($activeOutage));
        $this->assertFalse($activeOutages->contains($expiredByTime));
        $this->assertFalse($activeOutages->contains($closedOutage));
        $this->assertFalse($activeOutages->contains($expiredStatusOutage));
    }

    public function test_scope_within_bounds_filters_by_bounding_box(): void
    {
        $user = User::factory()->create();

        $caracasOutage = Outage::factory()->create([
            'user_id' => $user->id,
            'latitude' => 10.50000000,
            'longitude' => -66.91000000,
            'min_lat' => 10.48000000,
            'max_lat' => 10.52000000,
            'min_lng' => -66.93000000,
            'max_lng' => -66.89000000,
        ]);

        $maracaiboOutage = Outage::factory()->create([
            'user_id' => $user->id,
            'latitude' => 10.65000000,
            'longitude' => -71.65000000,
            'min_lat' => 10.63000000,
            'max_lat' => 10.67000000,
            'min_lng' => -71.67000000,
            'max_lng' => -71.63000000,
        ]);

        $results = Outage::query()->withinBounds(
            minLat: 10.45,
            minLng: -67.00,
            maxLat: 10.55,
            maxLng: -66.80
        )->get();

        $this->assertTrue($results->contains($caracasOutage));
        $this->assertFalse($results->contains($maracaiboOutage));
    }

    public function test_calculate_bounding_box_returns_valid_coordinates(): void
    {
        $centerLat = 10.50000000;
        $centerLng = -66.91000000;
        $radiusKm = 2.5;

        $box = Outage::calculateBoundingBox($centerLat, $centerLng, $radiusKm);

        $this->assertArrayHasKey('min_lat', $box);
        $this->assertArrayHasKey('max_lat', $box);
        $this->assertArrayHasKey('min_lng', $box);
        $this->assertArrayHasKey('max_lng', $box);

        $this->assertLessThan($centerLat, $box['min_lat']);
        $this->assertGreaterThan($centerLat, $box['max_lat']);
        $this->assertLessThan($centerLng, $box['min_lng']);
        $this->assertGreaterThan($centerLng, $box['max_lng']);
    }
}
