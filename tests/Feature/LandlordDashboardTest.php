<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TenantPlan;
use App\Enums\TenantStatus;
use App\Models\LandlordUser;
use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\LandlordUserSeeder;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Illuminate\Support\Str;
use Tests\TestCase;

class LandlordDashboardTest extends TestCase
{
    private LandlordUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TenantSeeder::class);
        $this->seed(TenantStaffSeeder::class);
        $this->seed(LandlordUserSeeder::class);

        /** @var LandlordUser $admin */
        $admin = LandlordUser::query()->where('email', 'admin@vetmanager.io')->firstOrFail();
        $this->admin = $admin;

        // Ensure base tenants are reset to known clean states
        Tenant::query()->where('slug', 'alphavet')->update([
            'name' => 'Alpha Pet Hospital',
            'plan' => TenantPlan::Hospital,
            'status' => TenantStatus::Active,
            'storage_limit_bytes' => 100 * 1024 * 1024 * 1024,
        ]);
    }

    public function test_unauthenticated_visitor_is_redirected_to_landlord_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('landlord.login'));
    }

    public function test_landlord_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('Landlord Operations Console')
            ->assertSee('Sign In to Operations Console');
    }

    public function test_landlord_superadmin_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@vetmanager.io',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('landlord.dashboard'))
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($this->admin, 'landlord');
    }

    public function test_landlord_login_fails_with_invalid_credentials(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@vetmanager.io',
            'password' => 'WrongPasswordSecret!',
        ]);

        $response->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('landlord');
    }

    public function test_inactive_landlord_user_cannot_login(): void
    {
        $this->admin->update(['is_active' => false]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@vetmanager.io',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('landlord');

        $this->admin->update(['is_active' => true]);
    }

    public function test_landlord_user_can_logout(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')->post('/logout');

        $response->assertRedirect(route('landlord.login'))
            ->assertSessionHas('success');

        $this->assertGuest('landlord');
    }

    public function test_landlord_dashboard_renders_successfully_with_metrics_and_tenant_list(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')->get('/');

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
            ->assertSee('Platform Superadmin')
            ->assertSee('Equine &amp; Farm Ambulatory Care', false);
    }

    public function test_landlord_dashboard_filtering_by_status_and_search(): void
    {
        // 1. Search by keyword
        $searchResponse = $this->actingAs($this->admin, 'landlord')->get('/?search=Alpha');
        $searchResponse->assertStatus(200)
            ->assertSee('Alpha Pet Hospital')
            ->assertDontSee('Equine &amp; Farm Ambulatory Care', false);

        // 2. Filter by Plan
        $planResponse = $this->actingAs($this->admin, 'landlord')->get('/?plan=clinic');
        $planResponse->assertStatus(200)
            ->assertSee('Equine &amp; Farm Ambulatory Care', false)
            ->assertDontSee('Alpha Pet Hospital');
    }

    public function test_landlord_can_inspect_clinic_metadata_via_json(): void
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();

        $response = $this->actingAs($this->admin, 'landlord')->getJson("/tenants/{$tenant->id}");

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
        $response = $this->actingAs($this->admin, 'landlord')->patch("/tenants/{$tenant->id}/status");
        $response->assertRedirect(route('landlord.dashboard'))
            ->assertSessionHas('success');

        $tenant->refresh();
        $this->assertTrue($tenant->isSuspended());
        $this->assertEquals(TenantStatus::Suspended, $tenant->status);

        // Toggle back to Active
        $response = $this->actingAs($this->admin, 'landlord')->patch("/tenants/{$tenant->id}/status");
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

        $response = $this->actingAs($this->admin, 'landlord')->put("/tenants/{$tenant->id}", [
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

        $response = $this->actingAs($this->admin, 'landlord')->post('/tenants', [
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

        $response = $this->actingAs($this->admin, 'landlord')->delete("/tenants/{$tenant->id}");
        $response->assertRedirect(route('landlord.dashboard'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('tenants', [
            'id' => $tenant->id,
        ], 'landlord');
    }
}
