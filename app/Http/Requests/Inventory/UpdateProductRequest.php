<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\DTOs\Inventory\UpdateProductDTO;
use App\Enums\ControlledSubstanceSchedule;
use App\Enums\MeasurementBaseUnit;
use App\Models\InventoryProduct;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        /** @var InventoryProduct|null $product */
        $product = $this->route('product');
        $productId = $product instanceof InventoryProduct ? $product->id : (string) $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tenant.inventory_products', 'sku')->ignore($productId),
            ],
            'base_unit' => ['nullable', Rule::enum(MeasurementBaseUnit::class)],
            'is_controlled' => ['nullable', 'boolean'],
            'controlled_schedule' => ['nullable', Rule::enum(ControlledSubstanceSchedule::class)],
            'reorder_threshold' => ['nullable', 'numeric', 'min:0'],
            'package_description' => ['nullable', 'string', 'max:255'],
            'packaging_factor' => ['nullable', 'numeric', 'min:0.0001'],
        ];
    }

    public function toDTO(): UpdateProductDTO
    {
        return UpdateProductDTO::fromArray($this->validated());
    }
}
