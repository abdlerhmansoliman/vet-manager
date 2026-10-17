<?php

declare(strict_types=1);

namespace App\Actions\Landlord;

use App\DTOs\Landlord\ProvisionTenantDTO;
use App\Enums\StaffRole;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProvisionTenantAction
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

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
            'status' => TenantStatus::Active,
            'plan' => $dto->plan,
        ]);

        // 1. Provision isolated physical MySQL schema
        $this->tenantManager->createDatabase($tenant);

        // 2. Run migrations on the isolated tenant database
        $this->tenantManager->switch($tenant);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        // 3. Create initial clinic owner user (auto-syncs to Landlord lookup via model boot)
        User::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $dto->ownerName,
            'email' => $dto->ownerEmail,
            'password' => Hash::make($dto->ownerPassword),
            'role' => StaffRole::Owner,
            'is_active' => true,
        ]);

        // 4. Reset connection back to landlord
        $this->tenantManager->reset();

        return $tenant;
    }
}
