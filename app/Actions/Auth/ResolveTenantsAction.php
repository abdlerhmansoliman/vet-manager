<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantStaffLookup;
use Illuminate\Support\Collection;

class ResolveTenantsAction
{
    /**
     * @return Collection<int, Tenant>
     */
    public function execute(string $email): Collection
    {
        $tenantIds = TenantStaffLookup::query()
            ->where('email', $email)
            ->pluck('tenant_id');

        if ($tenantIds->isEmpty()) {
            return collect();
        }

        return Tenant::query()
            ->whereIn('id', $tenantIds)
            ->where('status', TenantStatus::Active)
            ->get();
    }
}
