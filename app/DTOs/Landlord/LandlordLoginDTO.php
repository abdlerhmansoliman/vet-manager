<?php

declare(strict_types=1);

namespace App\DTOs\Landlord;

readonly class LandlordLoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: strtolower(trim((string) $data['email'])),
            password: (string) $data['password'],
            remember: (bool) ($data['remember'] ?? false),
        );
    }
}
