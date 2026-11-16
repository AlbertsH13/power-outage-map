<?php

namespace App\Actions;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Models\Outage;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateOutageAction
{
    /**
     * @param  array{radius_km: float|string, duration_type: string|DurationType, duration_hours?: int|string|null, description?: ?string}  $data
     */
    public function execute(User $user, Outage $outage, array $data): Outage
    {
        Gate::forUser($user)->authorize('update', $outage);

        if ($outage->status !== OutageStatus::Active || $outage->expires_at <= now()) {
            throw ValidationException::withMessages([
                'outage' => 'This report is no longer active.',
            ]);
        }

        $radiusKm = (float) $data['radius_km'];
        $durationType = $data['duration_type'] instanceof DurationType
            ? $data['duration_type']
            : DurationType::from($data['duration_type']);

        $bbox = Outage::calculateBoundingBox(
            lat: (float) $outage->latitude,
            lng: (float) $outage->longitude,
            radiusKm: $radiusKm
        );

        $hardCap = $outage->created_at->copy()->addHours((int) config('outage.until_closed_hard_cap_hours', 72));

        $expiresAt = match ($durationType) {
            DurationType::Fixed => now()->addHours((int) ($data['duration_hours'] ?? 1)),
            DurationType::UntilClosed => $hardCap,
        };

        if ($expiresAt->isAfter($hardCap)) {
            $expiresAt = $hardCap;
        }

        $outage->update([
            'radius_km' => $radiusKm,
            'min_lat' => $bbox['min_lat'],
            'max_lat' => $bbox['max_lat'],
            'min_lng' => $bbox['min_lng'],
            'max_lng' => $bbox['max_lng'],
            'duration_type' => $durationType,
            'duration_hours' => $durationType === DurationType::Fixed ? (int) $data['duration_hours'] : null,
            'expires_at' => $expiresAt,
            'description' => $data['description'] ?? null,
        ]);

        return $outage;
    }
}
