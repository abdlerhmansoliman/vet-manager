<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TenantPlan;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = [
            [
                'name' => 'Alpha Pet Hospital',
                'slug' => 'alphavet',
                'database_name' => 'vet_tenant_alphavet',
                'status' => TenantStatus::Active,
                'plan' => TenantPlan::Hospital,
                'storage_limit_bytes' => 100 * 1024 * 1024 * 1024,
            ],
            [
                'name' => 'Equine & Farm Ambulatory Care',
                'slug' => 'equinecare',
                'database_name' => 'vet_tenant_equinecare',
                'status' => TenantStatus::Active,
                'plan' => TenantPlan::Clinic,
                'storage_limit_bytes' => 20 * 1024 * 1024 * 1024,
            ],
        ];

        foreach ($tenants as $data) {
            Tenant::query()->firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['id' => (string) Str::uuid()])
            );
        }
    }
}
