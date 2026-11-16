<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CloseOutageAction;
use App\Actions\CreateOutageAction;
use App\Actions\UpdateOutageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\OutageViewportRequest;
use App\Http\Requests\StoreOutageRequest;
use App\Http\Requests\UpdateOutageRequest;
use App\Http\Resources\PublicOutageResource;
use App\Models\Outage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutageApiController extends Controller
{
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

    public function store(StoreOutageRequest $request, CreateOutageAction $createOutageAction): JsonResponse
    {
        $outage = $createOutageAction->execute($request->user(), $request->validated());

        return (new PublicOutageResource($outage))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateOutageRequest $request, Outage $outage, UpdateOutageAction $updateOutageAction): JsonResponse
    {
        $outage = $updateOutageAction->execute($request->user(), $outage, $request->validated());

        return (new PublicOutageResource($outage))
            ->response()
            ->setStatusCode(200);
    }

    public function close(Request $request, Outage $outage, CloseOutageAction $closeOutageAction): JsonResponse
    {
        $outage = $closeOutageAction->execute($request->user(), $outage);

        return (new PublicOutageResource($outage))
            ->response()
            ->setStatusCode(200);
    }
}
