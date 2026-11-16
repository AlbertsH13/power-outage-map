<?php

namespace App\Http\Controllers;

use App\Actions\CloseOutageAction;
use App\Actions\CreateOutageAction;
use App\Actions\UpdateOutageAction;
use App\Enums\OutageStatus;
use App\Http\Requests\StoreOutageRequest;
use App\Http\Requests\UpdateOutageRequest;
use App\Models\Outage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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

    public function store(StoreOutageRequest $request, CreateOutageAction $createOutageAction): RedirectResponse
    {
        $createOutageAction->execute($request->user(), $request->validated());

        return redirect()->route('reports.index')->with('status', 'Outage report submitted successfully.');
    }

    public function edit(Outage $outage): View
    {
        Gate::authorize('update', $outage);

        if ($outage->status !== OutageStatus::Active || $outage->expires_at <= now()) {
            abort(404);
        }

        return view('reports.edit', [
            'outage' => $outage,
        ]);
    }

    public function update(UpdateOutageRequest $request, Outage $outage, UpdateOutageAction $updateOutageAction): RedirectResponse
    {
        try {
            $updateOutageAction->execute($request->user(), $outage, $request->validated());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('reports.index')->with('status', 'Outage report updated successfully.');
    }

    public function close(Request $request, Outage $outage, CloseOutageAction $closeOutageAction): RedirectResponse
    {
        try {
            $closeOutageAction->execute($request->user(), $outage);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Report marked as resolved.');
    }
}
