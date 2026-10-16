<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\DTOs\Inventory\ConsumeStockDTO;
use App\Enums\StockMovementType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsumeStockRequest extends FormRequest
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
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'movement_type' => ['nullable', Rule::enum(StockMovementType::class)],
            'witness_id' => ['nullable', 'string', 'uuid', 'exists:tenant.users,id'],
            'witness_pin' => ['nullable', 'string', 'size:4'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDTO(): ConsumeStockDTO
    {
        return ConsumeStockDTO::fromArray($this->validated());
    }
}
