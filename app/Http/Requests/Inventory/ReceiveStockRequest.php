<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\DTOs\Inventory\ReceiveStockDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReceiveStockRequest extends FormRequest
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
            'product_id' => ['required', 'string', 'uuid', 'exists:tenant.inventory_products,id'],
            'batch_number' => ['required', 'string', 'max:100'],
            'expiration_date' => ['required', 'date', 'after:today'],
            'cost_per_base_unit' => ['nullable', 'numeric', 'min:0'],
            'containers_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'base_quantity_per_container' => ['required', 'numeric', 'min:0.0001'],
        ];
    }

    public function toDTO(): ReceiveStockDTO
    {
        return ReceiveStockDTO::fromArray($this->validated());
    }
}
