<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $deviceName = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: strtolower(trim((string) $data['email'])),
            password: (string) $data['password'],
            deviceName: isset($data['device_name']) ? (string) $data['device_name'] : null,
        );
    }
}
