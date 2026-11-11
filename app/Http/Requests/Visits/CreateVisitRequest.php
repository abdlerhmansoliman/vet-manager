<?php

declare(strict_types=1);

namespace App\Http\Requests\Visits;

use App\DTOs\Visits\CreateVisitDTO;
use Illuminate\Foundation\Http\FormRequest;

class CreateVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'string', 'uuid'],
            'title' => ['required', 'string', 'max:191'],
            'visit_type' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'max:32'],
            'reason_for_visit' => ['nullable', 'string', 'max:5000'],
            'visit_date' => ['nullable', 'date'],
        ];
    }

    public function toDTO(): CreateVisitDTO
    {
        return CreateVisitDTO::fromArray($this->validated());
    }
}
