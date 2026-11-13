<?php

declare(strict_types=1);

namespace App\Jobs\Landlord;

use App\Enums\StaffRole;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class ProvisionTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 180;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $ownerName,
        public readonly string $ownerEmail,
        public readonly string $ownerPassword
    ) {}

    /**
     * Execute the provisioning sequence.
     */
    public function handle(TenantManager $tenantManager): void
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::query()->findOrFail($this->tenantId);

        try {
            // 1. Provision isolated physical MySQL schema
            $tenantManager->createDatabase($tenant);

            // 2. Switch application context and tenant connection
            $tenantManager->switch($tenant);

            // 3. Run tenant migrations (which also seeds default system species)
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            // 4. Create initial clinic owner user (auto-syncs to Landlord lookup via model boot)
            User::query()->create([
                'id' => (string) Str::uuid(),
                'name' => $this->ownerName,
                'email' => $this->ownerEmail,
                'password' => Hash::make($this->ownerPassword),
                'role' => StaffRole::Owner,
                'is_active' => true,
            ]);

            // 5. Mark tenant status as Active upon successful completion
            $tenant->update(['status' => TenantStatus::Active]);

        } catch (Throwable $e) {
            // Atomic Rollback: Reset connection, drop database, and mark status as Failed
            $tenantManager->reset();

            try {
                $tenantManager->dropDatabase($tenant);
            } catch (Throwable) {
                // Ignore secondary errors during database drop rollback
            }

            $tenant->update(['status' => TenantStatus::Failed]);

            report($e);
            throw $e;
        } finally {
            // Invariant: Guarantees connection always resets back to Landlord
            $tenantManager->reset();
        }
    }
}
