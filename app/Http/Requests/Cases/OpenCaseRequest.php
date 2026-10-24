<?php

declare(strict_types=1);

namespace App\Http\Requests\Cases;

use App\DTOs\Cases\OpenCaseDTO;
use App\Enums\CasePriority;
use App\Enums\CaseType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class OpenCaseRequest extends FormRequest
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
            'patient_id' => ['required', 'string', 'uuid', 'exists:tenant.patients,id'],
            'primary_veterinarian_id' => ['required', 'string', 'uuid', 'exists:tenant.users,id'],
            'chief_complaint' => ['required', 'string', 'max:255'],
            'case_type' => ['nullable', new Enum(CaseType::class)],
            'priority' => ['nullable', new Enum(CasePriority::class)],
            'provisional_diagnosis' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toDTO(): OpenCaseDTO
    {
        return OpenCaseDTO::fromArray($this->validated());
    }
}
