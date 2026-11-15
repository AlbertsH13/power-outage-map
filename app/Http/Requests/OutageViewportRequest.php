<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OutageViewportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('bbox') && is_string($this->query('bbox'))) {
            $parts = explode(',', $this->query('bbox'));

            if (count($parts) === 4) {
                $this->merge([
                    'min_lat' => $this->query('min_lat', $parts[0]),
                    'min_lng' => $this->query('min_lng', $parts[1]),
                    'max_lat' => $this->query('max_lat', $parts[2]),
                    'max_lng' => $this->query('max_lng', $parts[3]),
                ]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'min_lat' => ['required', 'numeric', 'between:-90,90'],
            'max_lat' => ['required', 'numeric', 'between:-90,90', 'gte:min_lat'],
            'min_lng' => ['required', 'numeric', 'between:-180,180'],
            'max_lng' => ['required', 'numeric', 'between:-180,180', 'gte:min_lng'],
        ];
    }

    public function minLat(): float
    {
        return (float) $this->validated('min_lat');
    }

    public function minLng(): float
    {
        return (float) $this->validated('min_lng');
    }

    public function maxLat(): float
    {
        return (float) $this->validated('max_lat');
    }

    public function maxLng(): float
    {
        return (float) $this->validated('max_lng');
    }
}
