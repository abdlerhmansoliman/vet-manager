<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\DTOs\Staff\ListStaffDTO;
use App\Enums\StaffRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListStaffRequest extends FormRequest
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
            'role' => ['nullable', Rule::enum(StaffRole::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toDTO(): ListStaffDTO
    {
        return ListStaffDTO::fromArray($this->validated());
    }
}
