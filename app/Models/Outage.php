<?php

namespace App\Models;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Outage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'radius_km',
        'min_lat',
        'max_lat',
        'min_lng',
        'max_lng',
        'duration_type',
        'duration_hours',
        'status',
        'expires_at',
        'closed_at',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_km' => 'float',
            'min_lat' => 'float',
            'max_lat' => 'float',
            'min_lng' => 'float',
            'max_lng' => 'float',
            'duration_type' => DurationType::class,
            'duration_hours' => 'integer',
            'status' => OutageStatus::class,
            'expires_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * The reporting user that owns the outage report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include actively ongoing outages.
     * Dual-layer check: status must be Active AND expires_at must be in the future.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', OutageStatus::Active)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope a query to filter outages overlapping a visible map viewport bounding box.
     */
    public function scopeWithinBounds(Builder $query, float $minLat, float $minLng, float $maxLat, float $maxLng): Builder
    {
        return $query->where('max_lat', '>=', $minLat)
            ->where('min_lat', '<=', $maxLat)
            ->where('max_lng', '>=', $minLng)
            ->where('min_lng', '<=', $maxLng);
    }

    /**
     * Helper to compute bounding box coordinates given center lat/lng and radius in km.
     *
     * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}
     */
    public static function calculateBoundingBox(float $lat, float $lng, float $radiusKm): array
    {
        $deltaLat = $radiusKm / 111.32;
        $deltaLng = $radiusKm / (111.32 * cos(deg2rad($lat)));

        return [
            'min_lat' => round($lat - $deltaLat, 8),
            'max_lat' => round($lat + $deltaLat, 8),
            'min_lng' => round($lng - $deltaLng, 8),
            'max_lng' => round($lng + $deltaLng, 8),
        ];
    }
}
