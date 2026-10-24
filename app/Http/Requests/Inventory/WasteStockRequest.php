<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\DTOs\Inventory\ConsumeStockDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WasteStockRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:255'],
            'witness_id' => ['nullable', 'string', 'uuid', 'exists:tenant.users,id'],
            'witness_pin' => ['nullable', 'string', 'size:4'],
        ];
    }

    public function toDTO(): ConsumeStockDTO
    {
        return ConsumeStockDTO::fromArray($this->validated());
    }
}
