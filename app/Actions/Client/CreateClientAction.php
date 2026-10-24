<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\DTOs\Client\CreateClientDTO;
use App\Models\Client;

class CreateClientAction
{
    /**
     * Register a new clinic client (individual, farm manager, or enterprise).
     */
    public function execute(CreateClientDTO $dto): Client
    {
        return Client::create([
            'client_type' => $dto->clientType,
            'name' => $dto->name,
            'phone_primary' => $dto->phonePrimary,
            'contact_person' => $dto->contactPerson,
            'phone_secondary' => $dto->phoneSecondary,
            'physical_address' => $dto->physicalAddress,
            'notes' => $dto->notes,
        ]);
    }
}
