<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use App\DTOs\Landlord\UpdateTenantDTO;
use App\Enums\TenantPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTenantRequest extends FormRequest
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
            'plan' => ['required', new Enum(TenantPlan::class)],
            'storage_limit_gb' => ['required', 'integer', 'min:1', 'max:2000'],
        ];
    }

    public function toDTO(): UpdateTenantDTO
    {
        return UpdateTenantDTO::fromArray($this->validated());
    }
}
