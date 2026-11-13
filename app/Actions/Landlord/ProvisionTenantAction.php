<?php

declare(strict_types=1);

namespace App\Actions\Landlord;

use App\DTOs\Landlord\ProvisionTenantDTO;
use App\Enums\TenantStatus;
use App\Jobs\Landlord\ProvisionTenantJob;
use App\Models\Tenant;
use Illuminate\Support\Str;

class ProvisionTenantAction
{
    /**
     * Provision a brand-new veterinary clinic tenant.
     */
    public function execute(ProvisionTenantDTO $dto): Tenant
    {
        $tenantId = (string) Str::uuid();
        $safeSlug = preg_replace('/[^a-z0-9_]/', '', strtolower($dto->slug));
        $databaseName = 'vet_tenant_'.$safeSlug;

        /** @var Tenant $tenant */
        $tenant = Tenant::query()->create([
            'id' => $tenantId,
            'name' => $dto->name,
            'slug' => $safeSlug,
            'database_name' => $databaseName,
            'storage_limit_bytes' => $dto->storageLimitBytes,
            'storage_used_bytes' => 0,
            'status' => TenantStatus::Provisioning,
            'plan' => $dto->plan,
        ]);

        // Execute provisioning job with atomic rollback and connection isolation
        ProvisionTenantJob::dispatchSync(
            tenantId: $tenant->id,
            ownerName: $dto->ownerName,
            ownerEmail: $dto->ownerEmail,
            ownerPassword: $dto->ownerPassword,
        );

        return $tenant->fresh() ?? $tenant;
    }
}
