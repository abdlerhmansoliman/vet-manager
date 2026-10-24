<?php

declare(strict_types=1);

namespace App\Http\Requests\Patient;

use App\DTOs\Patient\CreatePatientDTO;
use App\Enums\PatientGender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreatePatientRequest extends FormRequest
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
            'client_id' => ['required', 'string', 'uuid', 'exists:tenant.clients,id'],
            'name_or_tag' => ['required', 'string', 'max:100'],
            'species' => ['required', 'string', 'max:64'],
            'gender' => ['required', new Enum(PatientGender::class)],
            'breed' => ['nullable', 'string', 'max:100'],
            'age' => ['nullable', 'integer', 'min:0', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'is_approximate_age' => ['nullable', 'boolean'],
            'microchip_number' => ['nullable', 'string', 'max:32'],
            'ear_tag_id' => ['nullable', 'string', 'max:64'],
            'coat_color_markings' => ['nullable', 'string', 'max:191'],
            'current_weight_kg' => ['nullable', 'numeric', 'min:0.001', 'max:9999.999'],
            'history' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string', 'max:100'],
        ];
    }

    public function toDTO(): CreatePatientDTO
    {
        return CreatePatientDTO::fromArray($this->validated());
    }
}
