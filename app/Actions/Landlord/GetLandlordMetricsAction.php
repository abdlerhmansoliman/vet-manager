<?php

declare(strict_types=1);

namespace App\Actions\Landlord;

use App\Enums\TenantPlan;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantStaffLookup;

class GetLandlordMetricsAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $totalClinics = Tenant::query()->count();
        $activeClinics = Tenant::query()->where('status', TenantStatus::Active)->count();
        $suspendedClinics = Tenant::query()->where('status', TenantStatus::Suspended)->count();

        $totalStorageLimit = (int) Tenant::query()->sum('storage_limit_bytes');
        $totalStorageUsed = (int) Tenant::query()->sum('storage_used_bytes');

        $totalStaff = TenantStaffLookup::query()->distinct('email')->count('email');

        $planCounts = [
            TenantPlan::Solo->value => Tenant::query()->where('plan', TenantPlan::Solo)->count(),
            TenantPlan::Clinic->value => Tenant::query()->where('plan', TenantPlan::Clinic)->count(),
            TenantPlan::Hospital->value => Tenant::query()->where('plan', TenantPlan::Hospital)->count(),
            TenantPlan::Enterprise->value => Tenant::query()->where('plan', TenantPlan::Enterprise)->count(),
        ];

        return [
            'total_clinics' => $totalClinics,
            'active_clinics' => $activeClinics,
            'suspended_clinics' => $suspendedClinics,
            'total_storage_limit_bytes' => $totalStorageLimit,
            'total_storage_used_bytes' => $totalStorageUsed,
            'total_staff' => $totalStaff,
            'plan_counts' => $planCounts,
        ];
    }
}
