<?php

declare(strict_types=1);

namespace App\Http\Requests\Visits;

use App\DTOs\Visits\UpdateVisitDTO;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:191'],
            'visit_type' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'max:32'],
            'reason_for_visit' => ['nullable', 'string', 'max:5000'],
            'is_signed' => ['nullable', 'boolean'],
            'signed_by' => ['nullable', 'string', 'max:191'],
        ];
    }

    public function toDTO(): UpdateVisitDTO
    {
        return UpdateVisitDTO::fromArray($this->validated());
    }
}
