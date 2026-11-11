<?php

declare(strict_types=1);

namespace App\Http\Requests\Cases;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdministerDoseRequest extends FormRequest
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
            'dose_amount_base_units' => ['required', 'numeric', 'min:0.0001'],
            'route_administered' => ['nullable', 'string', 'max:32'],
            'clinical_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
