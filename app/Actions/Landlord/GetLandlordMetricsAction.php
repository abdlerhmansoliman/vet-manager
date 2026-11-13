<?php

declare(strict_types=1);

namespace App\Actions\Landlord;

use App\Enums\TenantPlan;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantStaffLookup;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\DB;
use Throwable;

class GetLandlordMetricsAction
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $totalClinics = Tenant::query()->count();
        $activeClinics = Tenant::query()->where('status', TenantStatus::Active)->count();
        $suspendedClinics = Tenant::query()->where('status', TenantStatus::Suspended)->count();
        $provisioningClinics = Tenant::query()->where('status', TenantStatus::Provisioning)->count();
        $failedClinics = Tenant::query()->where('status', TenantStatus::Failed)->count();

        $totalStorageLimit = (int) Tenant::query()->sum('storage_limit_bytes');
        $totalStorageUsed = (int) Tenant::query()->sum('storage_used_bytes');

        $totalStaff = TenantStaffLookup::query()->distinct('email')->count('email');

        $planCounts = [
            TenantPlan::Solo->value => Tenant::query()->where('plan', TenantPlan::Solo)->count(),
            TenantPlan::Clinic->value => Tenant::query()->where('plan', TenantPlan::Clinic)->count(),
            TenantPlan::Hospital->value => Tenant::query()->where('plan', TenantPlan::Hospital)->count(),
            TenantPlan::Enterprise->value => Tenant::query()->where('plan', TenantPlan::Enterprise)->count(),
        ];

        // Platform clinical telemetry aggregated across active practice databases
        $totalPatients = 0;
        $totalCases = 0;
        $totalVisits = 0;
        $activeSpeciesCodes = [];

        $activeTenants = Tenant::query()->where('status', TenantStatus::Active)->get();

        foreach ($activeTenants as $tenant) {
            try {
                $this->tenantManager->switch($tenant);

                if (DB::connection('tenant')->getSchemaBuilder()->hasTable('patients')) {
                    $totalPatients += (int) DB::connection('tenant')->table('patients')->count();
                }

                if (DB::connection('tenant')->getSchemaBuilder()->hasTable('cases')) {
                    $totalCases += (int) DB::connection('tenant')->table('cases')->count();
                }

                if (DB::connection('tenant')->getSchemaBuilder()->hasTable('visits')) {
                    $totalVisits += (int) DB::connection('tenant')->table('visits')->count();
                }

                if (DB::connection('tenant')->getSchemaBuilder()->hasTable('species')) {
                    $codes = DB::connection('tenant')->table('species')
                        ->where('is_active', true)
                        ->pluck('code')
                        ->all();
                    foreach ($codes as $code) {
                        $activeSpeciesCodes[$code] = true;
                    }
                }
            } catch (Throwable) {
                // If a tenant database schema is temporarily unreachable, skip gracefully
            } finally {
                $this->tenantManager->reset();
            }
        }

        return [
            'total_clinics' => $totalClinics,
            'active_clinics' => $activeClinics,
            'suspended_clinics' => $suspendedClinics,
            'provisioning_clinics' => $provisioningClinics,
            'failed_clinics' => $failedClinics,
            'total_storage_limit_bytes' => $totalStorageLimit,
            'total_storage_used_bytes' => $totalStorageUsed,
            'total_staff' => $totalStaff,
            'plan_counts' => $planCounts,
            'total_patients' => $totalPatients,
            'total_cases' => $totalCases,
            'total_visits' => $totalVisits,
            'total_species' => count($activeSpeciesCodes),
        ];
    }
}
