<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\VerifyPinDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyPinRequest extends FormRequest
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
            'witness_id' => ['required_without:witness_email', 'nullable', 'string', 'uuid'],
            'witness_email' => ['required_without:witness_id', 'nullable', 'string', 'email'],
            'pin' => ['required', 'string', 'size:4'],
        ];
    }

    public function toDTO(): VerifyPinDTO
    {
        return VerifyPinDTO::fromArray($this->validated());
    }
}
