<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\DTOs\Client\ListClientsDTO;
use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListClientsAction
{
    /**
     * List clients with optional type filter and name/phone search.
     *
     * @return LengthAwarePaginator<Client>
     */
    public function execute(ListClientsDTO $dto, int $perPage = 20): LengthAwarePaginator
    {
        $query = Client::query()->withCount('activePatients');

        if ($dto->clientType !== null) {
            $query->where('client_type', $dto->clientType);
        }

        if ($dto->search !== null) {
            $query->where(function ($q) use ($dto): void {
                $q->where('name', 'like', "%{$dto->search}%")
                    ->orWhere('phone_primary', 'like', "%{$dto->search}%")
                    ->orWhere('contact_person', 'like', "%{$dto->search}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }
}
