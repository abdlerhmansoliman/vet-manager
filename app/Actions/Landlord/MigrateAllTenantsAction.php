<?php

declare(strict_types=1);

namespace App\Actions\Landlord;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class MigrateAllTenantsAction
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Execute database migrations across all active tenant schemas.
     *
     * @return array{total: int, migrated: int, failed: int, failures: list<array{tenant_id: string, tenant_name: string, error: string}>}
     */
    public function execute(): array
    {
        $tenants = Tenant::query()
            ->where('status', TenantStatus::Active)
            ->get();

        $migrated = 0;
        $failed = 0;
        $failures = [];

        foreach ($tenants as $tenant) {
            try {
                $this->tenantManager->createDatabase($tenant);
                $this->tenantManager->switch($tenant);

                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);

                $migrated++;
            } catch (Throwable $e) {
                $failed++;
                $failures[] = [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'error' => $e->getMessage(),
                ];
                report($e);
            } finally {
                $this->tenantManager->reset();
            }
        }

        return [
            'total' => $tenants->count(),
            'migrated' => $migrated,
            'failed' => $failed,
            'failures' => $failures,
        ];
    }
}
