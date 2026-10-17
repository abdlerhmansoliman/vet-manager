<?php

declare(strict_types=1);

namespace App\Actions\Landlord;

use App\DTOs\Landlord\UpdateTenantDTO;
use App\Models\Tenant;

class UpdateTenantAction
{
    public function execute(Tenant $tenant, UpdateTenantDTO $dto): Tenant
    {
        $tenant->update([
            'name' => $dto->name,
            'plan' => $dto->plan,
            'storage_limit_bytes' => $dto->storageLimitBytes,
        ]);

        return $tenant;
    }
}
