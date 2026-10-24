<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\Models\Client;
use Illuminate\Validation\ValidationException;

class DeleteClientAction
{
    /**
     * Soft-delete a clinic client, blocking deletion if active patients exist.
     *
     * @throws ValidationException
     */
    public function execute(Client $client): void
    {
        if ($client->activePatients()->exists()) {
            throw ValidationException::withMessages([
                'client' => ['Cannot delete a client with active patients. Discharge or transfer all patients first.'],
            ]);
        }

        $client->delete();
    }
}
