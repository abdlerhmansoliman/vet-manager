<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Models\TenantStaffLookup;
use App\Models\User;

class TenantStaffSyncService
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Synchronize a tenant user's email to the Landlord central lookup table.
     */
    public function syncUser(User $user, ?Tenant $tenant = null): void
    {
        $tenant ??= $this->tenantManager->current();

        if (! $tenant instanceof Tenant) {
            return;
        }

        // If user changed their email, remove the old lookup entry first
        if ($user->wasChanged('email') && $user->getOriginal('email')) {
            TenantStaffLookup::query()
                ->where('email', (string) $user->getOriginal('email'))
                ->where('tenant_id', $tenant->id)
                ->delete();
        }

        TenantStaffLookup::query()->firstOrCreate([
            'email' => strtolower(trim($user->email)),
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Remove a tenant user's email from the Landlord central lookup table.
     */
    public function deleteUser(User $user, ?Tenant $tenant = null): void
    {
        $tenant ??= $this->tenantManager->current();

        if (! $tenant instanceof Tenant) {
            return;
        }

        TenantStaffLookup::query()
            ->where('email', strtolower(trim($user->email)))
            ->where('tenant_id', $tenant->id)
            ->delete();
    }
}
