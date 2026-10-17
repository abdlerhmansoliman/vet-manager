<?php

declare(strict_types=1);

namespace App\Actions\Landlord;

use App\Enums\TenantStatus;
use App\Models\Tenant;

class ToggleTenantStatusAction
{
    public function execute(Tenant $tenant): Tenant
    {
        $newStatus = $tenant->isActive()
            ? TenantStatus::Suspended
            : TenantStatus::Active;

        $tenant->update(['status' => $newStatus]);

        return $tenant;
    }
}
