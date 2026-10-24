<?php

declare(strict_types=1);

namespace App\DTOs\Client;

use App\Enums\ClientType;

final readonly class ListClientsDTO
{
    public function __construct(
        public ?ClientType $clientType = null,
        public ?string $search = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $clientType = null;
        if (isset($data['client_type']) && $data['client_type'] !== '') {
            $clientType = $data['client_type'] instanceof ClientType
                ? $data['client_type']
                : ClientType::tryFrom((string) $data['client_type']);
        }

        return new self(
            clientType: $clientType,
            search: isset($data['search']) && trim((string) $data['search']) !== ''
                ? trim((string) $data['search'])
                : null,
        );
    }
}
