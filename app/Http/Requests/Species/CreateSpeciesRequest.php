<?php

declare(strict_types=1);

namespace App\Http\Requests\Species;

use App\DTOs\Species\CreateSpeciesDTO;
use Illuminate\Foundation\Http\FormRequest;

class CreateSpeciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'string', 'uuid'],
            'name' => ['required', 'string', 'max:64'],
            'code' => ['nullable', 'string', 'max:64'],
            'icon' => ['nullable', 'string', 'max:32'],
            'is_system_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toDTO(): CreateSpeciesDTO
    {
        return CreateSpeciesDTO::fromArray($this->validated());
    }
}
