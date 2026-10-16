<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;
use Illuminate\Console\Command;

class TenantDatabaseMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate 
                            {--tenant= : The UUID or slug of a specific tenant} 
                            {--fresh : Drop all tables and re-run all migrations for the tenant(s)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations across isolated tenant databases';

    public function __construct(
        private readonly TenantManager $tenantManager
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantIdentifier = $this->option('tenant');

        $query = Tenant::query();

        if ($tenantIdentifier !== null && $tenantIdentifier !== '') {
            $query->where(function ($q) use ($tenantIdentifier): void {
                $q->where('id', $tenantIdentifier)
                    ->orWhere('slug', $tenantIdentifier);
            });
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No matching tenants found to migrate.');

            return self::SUCCESS;
        }

        $isFresh = (bool) $this->option('fresh');

        foreach ($tenants as $tenant) {
            $this->info("─── Migrating Tenant: {$tenant->name} [{$tenant->slug}] (DB: {$tenant->database_name}) ───");

            // Ensure the tenant's isolated database schema exists
            $this->tenantManager->createDatabase($tenant);

            // Dynamically configure and switch connection
            $this->tenantManager->switch($tenant);

            $params = [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ];

            if ($isFresh) {
                $this->call('migrate:fresh', $params);
            } else {
                $this->call('migrate', $params);
            }

            $this->tenantManager->reset();
            $this->newLine();
        }

        $this->info('All tenant migrations completed successfully.');

        return self::SUCCESS;
    }
}
