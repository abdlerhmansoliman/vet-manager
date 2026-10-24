<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\DTOs\Client\UpdateClientDTO;
use App\Models\Client;

class UpdateClientAction
{
    /**
     * Update an existing clinic client record.
     */
    public function execute(Client $client, UpdateClientDTO $dto): Client
    {
        $client->update([
            'client_type' => $dto->clientType,
            'name' => $dto->name,
            'phone_primary' => $dto->phonePrimary,
            'contact_person' => $dto->contactPerson,
            'phone_secondary' => $dto->phoneSecondary,
            'physical_address' => $dto->physicalAddress,
            'notes' => $dto->notes,
        ]);

        return $client->refresh();
    }
}
