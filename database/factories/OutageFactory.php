<?php

namespace Database\Factories;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Models\Outage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Outage>
 */
class OutageFactory extends Factory
{
    protected $model = Outage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lat = fake()->latitude(10.45, 10.55);
        $lng = fake()->longitude(-66.95, -66.85);
        $radiusKm = fake()->randomFloat(2, 0.5, 15.0);
        $bounds = Outage::calculateBoundingBox($lat, $lng, $radiusKm);
        $durationHours = fake()->numberBetween(1, 24);

        return [
            'user_id' => User::factory(),
            'latitude' => $lat,
            'longitude' => $lng,
            'radius_km' => $radiusKm,
            'min_lat' => $bounds['min_lat'],
            'max_lat' => $bounds['max_lat'],
            'min_lng' => $bounds['min_lng'],
            'max_lng' => $bounds['max_lng'],
            'duration_type' => DurationType::Fixed,
            'duration_hours' => $durationHours,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours($durationHours),
            'closed_at' => null,
            'description' => fake()->sentence(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OutageStatus::Expired,
            'expires_at' => now()->subHours(2),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OutageStatus::Closed,
            'closed_at' => now()->subHour(),
        ]);
    }

    public function untilClosed(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_type' => DurationType::UntilClosed,
            'duration_hours' => null,
            'expires_at' => now()->addHours(72),
        ]);
    }
}
