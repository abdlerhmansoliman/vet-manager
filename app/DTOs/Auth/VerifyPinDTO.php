<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

final readonly class VerifyPinDTO
{
    public function __construct(
        public ?string $witnessId,
        public ?string $witnessEmail,
        public string $pin
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            witnessId: isset($data['witness_id']) ? (string) $data['witness_id'] : null,
            witnessEmail: isset($data['witness_email']) ? strtolower(trim((string) $data['witness_email'])) : null,
            pin: (string) $data['pin'],
        );
    }
}
