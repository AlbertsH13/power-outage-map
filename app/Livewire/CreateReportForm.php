<?php

namespace App\Livewire;

use App\Actions\CreateOutageAction;
use App\Enums\DurationType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CreateReportForm extends Component
{
    public float $latitude = 10.49200000;

    public float $longitude = -66.88000000;

    public float $radius_km = 2.0;

    public string $duration_type = 'fixed';

    public ?int $duration_hours = 4;

    public ?string $description = '';

    public function save(CreateOutageAction $createOutageAction)
    {
        $validated = $this->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
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

        try {
            $createOutageAction->execute(Auth::user(), $validated);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return;
        }

        session()->flash('status', 'Outage report submitted successfully.');

        return redirect()->route('reports.index');
    }

    public function render()
    {
        $activeCount = Auth::user()->outages()->active()->count();
        $maxActive = (int) config('outage.max_active_per_user', 3);

        return view('livewire.create-report-form', [
            'activeCount' => $activeCount,
            'maxActive' => $maxActive,
        ]);
    }
}
