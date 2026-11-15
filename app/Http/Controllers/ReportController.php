<?php

namespace App\Http\Controllers;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Http\Requests\StoreOutageRequest;
use App\Models\Outage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $activeOutages = $user->outages()
            ->active()
            ->latest()
            ->get();

        $pastOutages = $user->outages()
            ->where(function ($query) {
                $query->where('status', '!=', OutageStatus::Active)
                    ->orWhere('expires_at', '<=', now());
            })
            ->latest()
            ->paginate(10);

        $activeCount = $activeOutages->count();
        $maxActive = (int) config('outage.max_active_per_user', 3);

        return view('reports.index', [
            'activeOutages' => $activeOutages,
            'pastOutages' => $pastOutages,
            'activeCount' => $activeCount,
            'maxActive' => $maxActive,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        $activeCount = $user->outages()->active()->count();
        $maxActive = (int) config('outage.max_active_per_user', 3);

        return view('reports.create', [
            'activeCount' => $activeCount,
            'maxActive' => $maxActive,
        ]);
    }

    public function store(StoreOutageRequest $request): RedirectResponse
    {
        $user = $request->user();

        $activeCount = $user->outages()->active()->count();
        $maxActive = (int) config('outage.max_active_per_user', 3);

        if ($activeCount >= $maxActive) {
            return back()->withErrors([
                'outage' => "You have reached the maximum limit of {$maxActive} active reports.",
            ])->withInput();
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

        Outage::create([
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

        return redirect()->route('reports.index')->with('status', 'Outage report submitted successfully.');
    }

    public function close(Request $request, Outage $outage): RedirectResponse
    {
        Gate::authorize('close', $outage);

        if ($outage->status !== OutageStatus::Active || $outage->expires_at <= now()) {
            return back()->with('error', 'This report is no longer active.');
        }

        $outage->update([
            'status' => OutageStatus::Closed,
            'closed_at' => now(),
        ]);

        return back()->with('status', 'Report marked as resolved.');
    }
}
