<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\DTOs\Client\UpdateClientDTO;
use App\Enums\ClientType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateClientRequest extends FormRequest
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
            'client_type' => ['required', new Enum(ClientType::class)],
            'name' => ['required', 'string', 'max:191'],
            'phone_primary' => ['required', 'string', 'max:32'],
            'contact_person' => ['nullable', 'string', 'max:191'],
            'phone_secondary' => ['nullable', 'string', 'max:32'],
            'physical_address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toDTO(): UpdateClientDTO
    {
        return UpdateClientDTO::fromArray($this->validated());
    }
}
