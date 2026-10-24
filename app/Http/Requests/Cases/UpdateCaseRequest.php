<?php

declare(strict_types=1);

namespace App\Http\Requests\Cases;

use App\DTOs\Cases\UpdateCaseDTO;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCaseRequest extends FormRequest
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
            'primary_veterinarian_id' => ['required', 'string', 'uuid', 'exists:tenant.users,id'],
            'chief_complaint' => ['required', 'string', 'max:255'],
            'case_type' => ['required', new Enum(CaseType::class)],
            'status' => ['required', new Enum(CaseStatus::class)],
            'priority' => ['required', new Enum(CasePriority::class)],
            'provisional_diagnosis' => ['nullable', 'string', 'max:2000'],
            'final_diagnosis' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toDTO(): UpdateCaseDTO
    {
        return UpdateCaseDTO::fromArray($this->validated());
    }
}
