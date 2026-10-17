<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use App\DTOs\Landlord\ProvisionTenantDTO;
use App\Enums\TenantPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ProvisionTenantRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('landlord.tenants', 'slug')],
            'plan' => ['required', new Enum(TenantPlan::class)],
            'storage_limit_gb' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'owner_name' => ['required', 'string', 'max:100'],
            'owner_email' => ['required', 'email', 'max:191'],
            'owner_password' => ['required', 'string', 'min:8'],
        ];
    }

    public function toDTO(): ProvisionTenantDTO
    {
        return ProvisionTenantDTO::fromArray($this->validated());
    }
}
