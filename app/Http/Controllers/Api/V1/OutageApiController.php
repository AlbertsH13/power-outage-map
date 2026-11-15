<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OutageViewportRequest;
use App\Http\Requests\StoreOutageRequest;
use App\Http\Resources\PublicOutageResource;
use App\Models\Outage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OutageApiController extends Controller
{
    /**
     * Display a listing of active outages within the requested map viewport.
     */
    public function index(OutageViewportRequest $request): JsonResponse
    {
        $outages = Outage::query()
            ->active()
            ->withinBounds(
                minLat: $request->minLat(),
                minLng: $request->minLng(),
                maxLat: $request->maxLat(),
                maxLng: $request->maxLng(),
            )
            ->get();

        return PublicOutageResource::collection($outages)
            ->response()
            ->header('Cache-Control', 'public, max-age=15, stale-while-revalidate=30');
    }

    /**
     * Store a newly created outage report in storage.
     */
    public function store(StoreOutageRequest $request): JsonResponse
    {
        $user = $request->user();

        // Enforce maximum active reports limit per user (O(1) lookup via idx_outages_user_active)
        $activeCount = Outage::where('user_id', $user->id)
            ->where('status', OutageStatus::Active)
            ->where('expires_at', '>', now())
            ->count();

        $maxActive = config('outage.max_active_per_user', 3);

        if ($activeCount >= $maxActive) {
            return response()->json([
                'message' => "You have reached the maximum limit of {$maxActive} active reports.",
                'errors' => [
                    'outage' => ["You have reached the maximum limit of {$maxActive} active reports."],
                ],
            ], 422);
        }

        $validated = $request->validated();
        $durationType = DurationType::from($validated['duration_type']);

        $bbox = Outage::calculateBoundingBox(
            lat: (float) $validated['latitude'],
            lng: (float) $validated['longitude'],
            radiusKm: (float) $validated['radius_km']
        );

        $expiresAt = match ($durationType) {
            DurationType::Fixed => now()->addHours((int) $validated['duration_hours']),
            DurationType::UntilClosed => now()->addHours((int) config('outage.until_closed_hard_cap_hours', 72)),
        };

        $outage = Outage::create([
            'user_id' => $user->id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_km' => $validated['radius_km'],
            'min_lat' => $bbox['min_lat'],
            'max_lat' => $bbox['max_lat'],
            'min_lng' => $bbox['min_lng'],
            'max_lng' => $bbox['max_lng'],
            'duration_type' => $durationType,
            'duration_hours' => $durationType === DurationType::Fixed ? (int) $validated['duration_hours'] : null,
            'status' => OutageStatus::Active,
            'expires_at' => $expiresAt,
            'description' => $validated['description'] ?? null,
        ]);

        return (new PublicOutageResource($outage))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Manually close an active outage report.
     * Protected by OutagePolicy: only the creator can close their report.
     */
    public function close(Request $request, Outage $outage): JsonResponse
    {
        Gate::authorize('close', $outage);

        if ($outage->status !== OutageStatus::Active || $outage->expires_at <= now()) {
            return response()->json([
                'message' => 'This report is no longer active.',
            ], 422);
        }

        $outage->update([
            'status' => OutageStatus::Closed,
            'closed_at' => now(),
        ]);

        return (new PublicOutageResource($outage))
            ->response()
            ->setStatusCode(200);
    }
}
