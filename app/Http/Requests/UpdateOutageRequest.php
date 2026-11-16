<?php

namespace App\Http\Requests;

use App\Enums\DurationType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOutageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'radius_km' => ['required', 'numeric', 'between:0.50,25.00'],
            'duration_type' => ['required', Rule::enum(DurationType::class)],
            'duration_hours' => [
                'required_if:duration_type,'.DurationType::Fixed->value,
                'nullable',
                'integer',
                'between:1,72',
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
