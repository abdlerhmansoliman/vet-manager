<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TenantPlan;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Illuminate\Support\Str;
use Tests\TestCase;

class LandlordDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TenantSeeder::class);
        $this->seed(TenantStaffSeeder::class);

        // Ensure base tenants are reset to known clean states
        Tenant::query()->where('slug', 'alphavet')->update([
            'name' => 'Alpha Pet Hospital',
            'plan' => TenantPlan::Hospital,
            'status' => TenantStatus::Active,
            'storage_limit_bytes' => 100 * 1024 * 1024 * 1024,
        ]);
    }

    public function test_landlord_dashboard_renders_successfully_with_metrics_and_tenant_list(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertViewIs('landlord.dashboard')
            ->assertViewHasAll([
                'tenants',
                'metrics',
                'availablePlans',
                'availableStatuses',
                'globalStaffCount',
            ])
            ->assertSee('Multi-Clinic Operations Console')
            ->assertSee('Alpha Pet Hospital')
            ->assertSee('Equine &amp; Farm Ambulatory Care', false);
    }

    public function test_landlord_dashboard_filtering_by_status_and_search(): void
    {
        // 1. Search by keyword
        $searchResponse = $this->get('/?search=Alpha');
        $searchResponse->assertStatus(200)
            ->assertSee('Alpha Pet Hospital')
            ->assertDontSee('Equine &amp; Farm Ambulatory Care', false);

        // 2. Filter by Plan
        $planResponse = $this->get('/?plan=clinic');
        $planResponse->assertStatus(200)
            ->assertSee('Equine &amp; Farm Ambulatory Care', false)
            ->assertDontSee('Alpha Pet Hospital');
    }

    public function test_landlord_can_inspect_clinic_metadata_via_json(): void
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();

        $response = $this->getJson("/tenants/{$tenant->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => 'alphavet',
                'database_name' => 'vet_tenant_alphavet',
                'status' => 'active',
            ])
            ->assertJsonStructure([
                'id',
                'name',
                'slug',
                'database_name',
                'plan',
                'status',
                'storage_limit_formatted',
                'storage_used_formatted',
                'storage_percentage',
                'staff_count',
                'staff_emails',
            ]);
    }

    public function test_landlord_can_toggle_tenant_status_between_active_and_suspended(): void
    {
        $slug = 'togtest'.substr(Str::slug((string) Str::uuid()), 0, 6);
        /** @var Tenant $tenant */
        $tenant = Tenant::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Toggle Test Clinic',
            'slug' => $slug,
            'database_name' => "vet_tenant_{$slug}",
            'storage_limit_bytes' => 10 * 1024 * 1024 * 1024,
            'storage_used_bytes' => 0,
            'status' => TenantStatus::Active,
            'plan' => TenantPlan::Clinic,
        ]);

        // Toggle to Suspended
        $response = $this->patch("/tenants/{$tenant->id}/status");
        $response->assertRedirect(route('landlord.dashboard'))
            ->assertSessionHas('success');

        $tenant->refresh();
        $this->assertTrue($tenant->isSuspended());
        $this->assertEquals(TenantStatus::Suspended, $tenant->status);

        // Toggle back to Active
        $response = $this->patch("/tenants/{$tenant->id}/status");
        $response->assertRedirect(route('landlord.dashboard'));

        $tenant->refresh();
        $this->assertTrue($tenant->isActive());

        $tenant->delete();
    }

    public function test_landlord_can_update_clinic_plan_and_storage_quota(): void
    {
        $slug = 'updtest'.substr(Str::slug((string) Str::uuid()), 0, 6);
        /** @var Tenant $tenant */
        $tenant = Tenant::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Original Clinic Name',
            'slug' => $slug,
            'database_name' => "vet_tenant_{$slug}",
            'storage_limit_bytes' => 10 * 1024 * 1024 * 1024,
            'storage_used_bytes' => 0,
            'status' => TenantStatus::Active,
            'plan' => TenantPlan::Solo,
        ]);

        $response = $this->put("/tenants/{$tenant->id}", [
            'name' => 'Updated Clinic Name',
            'plan' => 'enterprise',
            'storage_limit_gb' => 350,
        ]);

        $response->assertRedirect(route('landlord.dashboard'))
            ->assertSessionHas('success');

        $tenant->refresh();
        $this->assertEquals('Updated Clinic Name', $tenant->name);
        $this->assertEquals(TenantPlan::Enterprise, $tenant->plan);
        $this->assertEquals(350 * 1024 * 1024 * 1024, $tenant->storage_limit_bytes);

        $tenant->delete();
    }

    public function test_landlord_can_provision_new_clinic_tenant_with_database_and_owner(): void
    {
        $uniqueSuffix = substr(Str::slug((string) Str::uuid()), 0, 8);
        $slug = 'provtest'.$uniqueSuffix;
        $email = "owner.{$slug}@provtestvet.com";

        $response = $this->post('/tenants', [
            'name' => 'Provisioned Test Clinic',
            'slug' => $slug,
            'plan' => 'clinic',
            'storage_limit_gb' => 25,
            'owner_name' => 'Dr. Robert Provisioner',
            'owner_email' => $email,
            'owner_password' => 'SecurePass1234!',
        ]);

        $response->assertRedirect(route('landlord.dashboard'))
            ->assertSessionHas('success');

        /** @var Tenant|null $tenant */
        $tenant = Tenant::query()->where('slug', $slug)->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('Provisioned Test Clinic', $tenant->name);
        $this->assertEquals("vet_tenant_{$slug}", $tenant->database_name);
        $this->assertEquals(TenantPlan::Clinic, $tenant->plan);
        $this->assertEquals(25 * 1024 * 1024 * 1024, $tenant->storage_limit_bytes);

        // Verify owner synced to central discovery lookup
        $this->assertDatabaseHas('tenant_staff_lookup', [
            'email' => $email,
            'tenant_id' => $tenant->id,
        ], 'landlord');

        // Cleanup created test database
        app(TenantManager::class)->dropDatabase($tenant);
        $tenant->delete();
    }

    public function test_landlord_can_purge_tenant_and_drop_database(): void
    {
        $uniqueSuffix = substr(Str::slug((string) Str::uuid()), 0, 8);
        $slug = 'purgetest'.$uniqueSuffix;

        /** @var Tenant $tenant */
        $tenant = Tenant::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Purgeable Practice',
            'slug' => $slug,
            'database_name' => "vet_tenant_{$slug}",
            'storage_limit_bytes' => 10 * 1024 * 1024 * 1024,
            'storage_used_bytes' => 0,
            'status' => TenantStatus::Active,
            'plan' => TenantPlan::Solo,
        ]);

        app(TenantManager::class)->createDatabase($tenant);

        $response = $this->delete("/tenants/{$tenant->id}");
        $response->assertRedirect(route('landlord.dashboard'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('tenants', [
            'id' => $tenant->id,
        ], 'landlord');
    }
}
