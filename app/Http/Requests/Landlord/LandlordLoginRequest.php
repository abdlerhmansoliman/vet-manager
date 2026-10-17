<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord;

use App\DTOs\Landlord\LandlordLoginDTO;
use Illuminate\Foundation\Http\FormRequest;

class LandlordLoginRequest extends FormRequest
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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function toDTO(): LandlordLoginDTO
    {
        return LandlordLoginDTO::fromArray($this->validated());
    }
}
