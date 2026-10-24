<?php

declare(strict_types=1);

namespace App\DTOs\Client;

use App\Enums\ClientType;

final readonly class UpdateClientDTO
{
    public function __construct(
        public ClientType $clientType,
        public string $name,
        public string $phonePrimary,
        public ?string $contactPerson = null,
        public ?string $phoneSecondary = null,
        public ?string $physicalAddress = null,
        public ?string $notes = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $clientType = $data['client_type'] instanceof ClientType
            ? $data['client_type']
            : ClientType::from((string) $data['client_type']);

        return new self(
            clientType: $clientType,
            name: trim((string) $data['name']),
            phonePrimary: (string) $data['phone_primary'],
            contactPerson: isset($data['contact_person']) ? trim((string) $data['contact_person']) : null,
            phoneSecondary: isset($data['phone_secondary']) ? (string) $data['phone_secondary'] : null,
            physicalAddress: isset($data['physical_address']) ? trim((string) $data['physical_address']) : null,
            notes: isset($data['notes']) && trim((string) $data['notes']) !== '' ? trim((string) $data['notes']) : null,
        );
    }
}
