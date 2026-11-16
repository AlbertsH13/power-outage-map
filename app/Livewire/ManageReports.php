<?php

namespace App\Livewire;

use App\Actions\CloseOutageAction;
use App\Actions\UpdateOutageAction;
use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Models\Outage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ManageReports extends Component
{
    use WithPagination;

    public ?int $editOutageId = null;

    public float $radius_km = 1.0;

    public string $duration_type = 'fixed';

    public ?int $duration_hours = 4;

    public ?string $description = '';

    public bool $showEditModal = false;

    public function closeReport(int $outageId, CloseOutageAction $closeOutageAction): void
    {
        $outage = Outage::findOrFail($outageId);

        $closeOutageAction->execute(auth()->user(), $outage);

        session()->flash('status', 'Report marked as resolved.');
    }

    public function openEditModal(int $outageId): void
    {
        $outage = Outage::findOrFail($outageId);

        Gate::authorize('update', $outage);

        $this->editOutageId = $outage->id;
        $this->radius_km = (float) $outage->radius_km;
        $this->duration_type = $outage->duration_type->value;
        $this->duration_hours = $outage->duration_hours ?? 4;
        $this->description = $outage->description;
        $this->showEditModal = true;
    }

    public function updateReport(UpdateOutageAction $updateOutageAction): void
    {
        $outage = Outage::findOrFail($this->editOutageId);

        $validated = $this->validate([
            'radius_km' => ['required', 'numeric', 'between:0.50,25.00'],
            'duration_type' => ['required', Rule::enum(DurationType::class)],
            'duration_hours' => [
                'required_if:duration_type,'.DurationType::Fixed->value,
                'nullable',
                'integer',
                'between:1,72',
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $updateOutageAction->execute(auth()->user(), $outage, $validated);

        $this->showEditModal = false;
        session()->flash('status', 'Outage report updated successfully.');
    }

    public function render()
    {
        $user = auth()->user();

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

        return view('livewire.manage-reports', [
            'activeOutages' => $activeOutages,
            'pastOutages' => $pastOutages,
            'activeCount' => $activeCount,
            'maxActive' => $maxActive,
        ]);
    }
}
