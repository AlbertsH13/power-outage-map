<?php

namespace App\Actions;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Models\Outage;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreateOutageAction
{
    /**
     * @param  array{latitude: float|string, longitude: float|string, radius_km: float|string, duration_type: string|DurationType, duration_hours?: int|string|null, description?: ?string}  $data
     */
    public function execute(User $user, array $data): Outage
    {
        $maxActive = (int) config('outage.max_active_per_user', 3);

        $activeCount = Outage::where('user_id', $user->id)
            ->active()
            ->count();

        if ($activeCount >= $maxActive) {
            throw ValidationException::withMessages([
                'outage' => "You have reached the maximum limit of {$maxActive} active reports.",
            ]);
        }

        $durationType = $data['duration_type'] instanceof DurationType
            ? $data['duration_type']
            : DurationType::from($data['duration_type']);

        $lat = (float) $data['latitude'];
        $lng = (float) $data['longitude'];
        $radiusKm = (float) $data['radius_km'];

        $bbox = Outage::calculateBoundingBox(lat: $lat, lng: $lng, radiusKm: $radiusKm);

        $expiresAt = match ($durationType) {
            DurationType::Fixed => now()->addHours((int) ($data['duration_hours'] ?? 1)),
            DurationType::UntilClosed => now()->addHours((int) config('outage.until_closed_hard_cap_hours', 72)),
        };

        return Outage::create([
            'user_id' => $user->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'radius_km' => $radiusKm,
            'min_lat' => $bbox['min_lat'],
            'max_lat' => $bbox['max_lat'],
            'min_lng' => $bbox['min_lng'],
            'max_lng' => $bbox['max_lng'],
            'duration_type' => $durationType,
            'duration_hours' => $durationType === DurationType::Fixed ? (int) $data['duration_hours'] : null,
            'status' => OutageStatus::Active,
            'expires_at' => $expiresAt,
            'description' => $data['description'] ?? null,
        ]);
    }
}
