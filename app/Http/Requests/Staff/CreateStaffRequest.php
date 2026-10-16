<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\DTOs\Staff\CreateStaffDTO;
use App\Enums\StaffRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->canManageStaff() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:tenant.users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(StaffRole::class)],
            'phone' => ['nullable', 'string', 'max:50'],
            'pin' => ['nullable', 'string', 'size:4'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'specialty' => ['nullable', 'string', 'max:100'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'bio' => ['nullable', 'string'],
            'is_available' => ['nullable', 'boolean'],
        ];
    }

    public function toDTO(): CreateStaffDTO
    {
        return CreateStaffDTO::fromArray($this->validated());
    }
}
