<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\DTOs\Inventory\CreateProductDTO;
use App\Enums\ControlledSubstanceSchedule;
use App\Enums\MeasurementBaseUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:tenant.inventory_products,sku'],
            'base_unit' => ['required', Rule::enum(MeasurementBaseUnit::class)],
            'is_controlled' => ['nullable', 'boolean'],
            'controlled_schedule' => ['nullable', Rule::enum(ControlledSubstanceSchedule::class)],
            'reorder_threshold' => ['nullable', 'numeric', 'min:0'],
            'package_description' => ['nullable', 'string', 'max:255'],
            'packaging_factor' => ['nullable', 'numeric', 'min:0.0001'],
        ];
    }

    public function toDTO(): CreateProductDTO
    {
        return CreateProductDTO::fromArray($this->validated());
    }
}
