<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\DTOs\Staff\UpdateAvailabilityDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
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
            'is_available' => ['required', 'boolean'],
        ];
    }

    public function toDTO(): UpdateAvailabilityDTO
    {
        return UpdateAvailabilityDTO::fromArray($this->validated());
    }
}
