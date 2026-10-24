<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\DTOs\Client\ListClientsDTO;
use App\Enums\ClientType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListClientsRequest extends FormRequest
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
            'client_type' => ['nullable', new Enum(ClientType::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toDTO(): ListClientsDTO
    {
        return ListClientsDTO::fromArray($this->validated());
    }
}
