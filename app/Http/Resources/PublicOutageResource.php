<?php

namespace App\Http\Resources;

use App\Models\Outage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Outage
 */
class PublicOutageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'radius_km' => (float) $this->radius_km,
            'bounding_box' => [
                'min_lat' => (float) $this->min_lat,
                'max_lat' => (float) $this->max_lat,
                'min_lng' => (float) $this->min_lng,
                'max_lng' => (float) $this->max_lng,
            ],
            'status' => $this->status->value,
            'duration_type' => $this->duration_type->value,
            'duration_hours' => $this->duration_hours !== null ? (int) $this->duration_hours : null,
            'expires_at' => $this->expires_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'description' => $this->description,
            'is_owner' => $request->user()?->id === $this->user_id,
        ];
    }
}
