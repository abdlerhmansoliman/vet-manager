<?php

declare(strict_types=1);

namespace App\Actions\Landlord;

use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Artisan;

class MigrateTenantAction
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    public function execute(Tenant $tenant): void
    {
        $this->tenantManager->createDatabase($tenant);
        $this->tenantManager->switch($tenant);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        $this->tenantManager->reset();
    }
}
