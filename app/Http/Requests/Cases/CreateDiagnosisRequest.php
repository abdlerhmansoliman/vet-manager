<?php

declare(strict_types=1);

namespace App\Http\Requests\Cases;

use App\DTOs\Cases\CreateDiagnosisDTO;
use App\Enums\DiagnosisStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateDiagnosisRequest extends FormRequest
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
            'diagnosis' => ['required', 'string', 'max:255'],
            'status' => ['nullable', new Enum(DiagnosisStatus::class)],
            'description' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'diagnosed_at' => ['nullable', 'date'],
        ];
    }

    public function toDTO(): CreateDiagnosisDTO
    {
        return CreateDiagnosisDTO::fromArray($this->validated());
    }
}
