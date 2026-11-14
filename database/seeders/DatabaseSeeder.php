<?php

namespace Database\Seeders;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Models\Outage;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with demo users and realistic outages.
     */
    public function run(): void
    {
        // 1. Primary demo user (required for testing / evaluator review)
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Secondary community user
        $communityUser = User::firstOrCreate(
            ['email' => 'jane@example.com'],
            [
                'name' => 'Jane Doe',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 3. Realistic sample outages around a metropolitan cluster (Caracas coordinate center)
        $sampleLocations = [
            ['lat' => 10.4910, 'lng' => -66.8620, 'radius' => 2.50, 'desc' => 'Substation transformer failure affecting downtown grid.'],
            ['lat' => 10.4780, 'lng' => -66.8400, 'radius' => 1.20, 'desc' => 'Downed power line on 5th Avenue after heavy winds.'],
            ['lat' => 10.5050, 'lng' => -66.8850, 'radius' => 4.00, 'desc' => 'High voltage cable repair in northern district.'],
            ['lat' => 10.4650, 'lng' => -66.9010, 'radius' => 0.80, 'desc' => 'Local breaker tripped on residential sector.'],
            ['lat' => 10.5120, 'lng' => -66.9150, 'radius' => 3.50, 'desc' => 'Scheduled maintenance on industrial feeder line.'],
        ];

        foreach ($sampleLocations as $i => $loc) {
            $user = ($i % 2 === 0) ? $demoUser : $communityUser;
            $bounds = Outage::calculateBoundingBox($loc['lat'], $loc['lng'], $loc['radius']);

            Outage::create([
                'user_id' => $user->id,
                'latitude' => $loc['lat'],
                'longitude' => $loc['lng'],
                'radius_km' => $loc['radius'],
                'min_lat' => $bounds['min_lat'],
                'max_lat' => $bounds['max_lat'],
                'min_lng' => $bounds['min_lng'],
                'max_lng' => $bounds['max_lng'],
                'duration_type' => DurationType::Fixed,
                'duration_hours' => 6,
                'status' => OutageStatus::Active,
                'expires_at' => now()->addHours(4),
                'closed_at' => null,
                'description' => $loc['desc'],
            ]);
        }

        // 4. One "Until closed" active outage (72h safety cap)
        $openBounds = Outage::calculateBoundingBox(10.4850, -66.8750, 1.80);
        Outage::create([
            'user_id' => $demoUser->id,
            'latitude' => 10.4850,
            'longitude' => -66.8750,
            'radius_km' => 1.80,
            'min_lat' => $openBounds['min_lat'],
            'max_lat' => $openBounds['max_lat'],
            'min_lng' => $openBounds['min_lng'],
            'max_lng' => $openBounds['max_lng'],
            'duration_type' => DurationType::UntilClosed,
            'duration_hours' => null,
            'status' => OutageStatus::Active,
            'expires_at' => now()->addHours(72),
            'closed_at' => null,
            'description' => 'Unconfirmed outage awaiting utility inspection.',
        ]);

        // 5. Sample past outages (expired and manually closed)
        $expiredBounds = Outage::calculateBoundingBox(10.4700, -66.8200, 2.00);
        Outage::create([
            'user_id' => $communityUser->id,
            'latitude' => 10.4700,
            'longitude' => -66.8200,
            'radius_km' => 2.00,
            'min_lat' => $expiredBounds['min_lat'],
            'max_lat' => $expiredBounds['max_lat'],
            'min_lng' => $expiredBounds['min_lng'],
            'max_lng' => $expiredBounds['max_lng'],
            'duration_type' => DurationType::Fixed,
            'duration_hours' => 3,
            'status' => OutageStatus::Expired,
            'expires_at' => now()->subHours(2),
            'closed_at' => null,
            'description' => 'Past outage resolved earlier today.',
        ]);

        $closedBounds = Outage::calculateBoundingBox(10.5000, -66.8500, 1.00);
        Outage::create([
            'user_id' => $demoUser->id,
            'latitude' => 10.5000,
            'longitude' => -66.8500,
            'radius_km' => 1.00,
            'min_lat' => $closedBounds['min_lat'],
            'max_lat' => $closedBounds['max_lat'],
            'min_lng' => $closedBounds['min_lng'],
            'max_lng' => $closedBounds['max_lng'],
            'duration_type' => DurationType::UntilClosed,
            'duration_hours' => null,
            'status' => OutageStatus::Closed,
            'expires_at' => now()->addHours(60),
            'closed_at' => now()->subMinutes(45),
            'description' => 'Power restored by grid operator.',
        ]);
    }
}
