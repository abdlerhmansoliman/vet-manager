<?php

declare(strict_types=1);

namespace App\Http\Requests\Cases;

use App\DTOs\Cases\CreateClinicalProgressNoteDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateClinicalProgressNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vet_id' => ['nullable', 'string', 'uuid', 'exists:tenant.users,id'],
            'temperature_c' => ['nullable', 'numeric', 'between:30.0,45.0'],
            'heart_rate_bpm' => ['nullable', 'integer', 'min:10', 'max:400'],
            'respiratory_rate' => ['nullable', 'integer', 'min:1', 'max:200'],
            'systolic_bp' => ['nullable', 'integer', 'min:40', 'max:300'],
            'diastolic_bp' => ['nullable', 'integer', 'min:20', 'max:200'],
            'mucous_membrane' => ['nullable', 'string', 'max:255'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.01', 'max:2000.00'],
            'subjective_notes' => ['nullable', 'string', 'max:10000'],
            'objective_notes' => ['nullable', 'string', 'max:10000'],
            'assessment_notes' => ['nullable', 'string', 'max:10000'],
            'plan_notes' => ['nullable', 'string', 'max:10000'],
            'images' => ['nullable', 'array'],
            'images.*.url' => ['required_with:images', 'string', 'url', 'max:2048'],
            'images.*.title' => ['nullable', 'string', 'max:255'],
            'images.*.mime_type' => ['nullable', 'string', 'max:100'],
            'images.*.file_size_bytes' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toDTO(): CreateClinicalProgressNoteDTO
    {
        return CreateClinicalProgressNoteDTO::fromArray($this->validated());
    }
}
