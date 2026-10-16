<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

final readonly class ResolveTenantsDTO
{
    public function __construct(
        public string $email
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: strtolower(trim((string) $data['email'])),
        );
    }
}
