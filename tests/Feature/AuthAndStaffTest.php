<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StaffRole;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Tests\TestCase;

class AuthAndStaffTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TenantSeeder::class);
        $this->seed(TenantStaffSeeder::class);
    }

    public function test_multi_tenant_email_discovery_returns_all_associated_clinics(): void
    {
        $response = $this->postJson('/api/v1/auth/resolve-tenants', [
            'email' => 'sarah.connor@vetservices.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['slug' => 'alphavet'])
            ->assertJsonFragment(['slug' => 'equinecare']);
    }

    public function test_staff_can_login_to_tenant_and_receive_capabilities(): void
    {
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/login', [
                'email' => 'john.miller@alphavet.com',
                'password' => 'password123',
                'device_name' => 'TestDevice',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'profile' => ['job_title', 'specialty', 'license_number'],
                ],
                'tenant' => ['id', 'name', 'slug'],
                'capabilities' => [
                    'can_manage_staff',
                    'can_witness_controlled_substances',
                    'can_access_owner_dashboard',
                ],
            ]);

        $this->assertTrue($response->json('capabilities.can_manage_staff'));
        $this->assertTrue($response->json('capabilities.can_access_owner_dashboard'));
    }

    public function test_token_for_one_tenant_is_rejected_on_another_tenant(): void
    {
        $loginResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/login', [
                'email' => 'john.miller@alphavet.com',
                'password' => 'password123',
            ]);

        $token = $loginResponse->json('token');

        // Requesting with the valid tenant succeeds
        $validResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $validResponse->assertStatus(200);

        // Cross-tenant replay attempt is rejected with 401
        $crossTenantResponse = $this->withHeader('X-Tenant-ID', 'equinecare')
            ->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $crossTenantResponse->assertStatus(401);
    }

    public function test_witness_pin_verification_for_controlled_substances(): void
    {
        $loginResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/login', [
                'email' => 'john.miller@alphavet.com',
                'password' => 'password123',
            ]);

        $token = $loginResponse->json('token');

        // Invalid PIN returns 422
        $invalidPinResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/verify-pin', [
                'witness_email' => 'sarah.connor@vetservices.com',
                'pin' => '0000',
            ]);

        $invalidPinResponse->assertStatus(422)
            ->assertJsonValidationErrors(['pin']);

        // Valid PIN returns 200 with verified witness details
        $validPinResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/verify-pin', [
                'witness_email' => 'sarah.connor@vetservices.com',
                'pin' => '5678',
            ]);

        $validPinResponse->assertStatus(200)
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.role', 'field_doctor');
    }

    public function test_suspended_tenant_immediately_blocks_requests_via_cache_invalidation(): void
    {
        // Populate cache
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->getJson('/api/tenant/info')
            ->assertStatus(200);

        // Suspend the tenant
        $tenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        $tenant->update(['status' => TenantStatus::Suspended]);

        // Subsequent request is immediately rejected with 403 Forbidden
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->getJson('/api/tenant/info');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Tenant account suspended or inactive.']);

        // Restore for other tests
        $tenant->update(['status' => TenantStatus::Active]);
    }

    public function test_owner_can_create_staff_and_syncs_to_landlord_discovery(): void
    {
        $loginResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/login', [
                'email' => 'john.miller@alphavet.com',
                'password' => 'password123',
            ]);

        $token = $loginResponse->json('token');

        $newStaffEmail = 'alex.'.uniqid().'@alphavet.com';

        $createResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/staff', [
                'name' => 'Alex Veterinary Tech',
                'email' => $newStaffEmail,
                'password' => 'secret1234',
                'role' => StaffRole::VetTech->value,
                'phone' => '+1-555-9000',
                'pin' => '9999',
                'job_title' => 'Surgical Technician',
                'specialty' => 'Anesthesia Monitoring',
                'is_available' => true,
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.email', $newStaffEmail)
            ->assertJsonPath('data.role', 'vet_tech');

        // Verify synced into Landlord discovery table
        $alphaTenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();

        $this->assertDatabaseHas('tenant_staff_lookup', [
            'email' => $newStaffEmail,
            'tenant_id' => $alphaTenant->id,
        ], 'landlord');
    }

    public function test_clinic_can_create_doctor_with_surgery_specialty_and_capabilities(): void
    {
        $loginResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/login', [
                'email' => 'john.miller@alphavet.com',
                'password' => 'password123',
            ]);

        $token = $loginResponse->json('token');
        $doctorEmail = 'dr.david.'.uniqid().'@alphavet.com';

        $createResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/staff', [
                'name' => 'Dr. David Anderson',
                'email' => $doctorEmail,
                'password' => 'surgerypass123',
                'role' => StaffRole::Doctor->value,
                'phone' => '+1-555-4422',
                'pin' => '1122',
                'job_title' => 'Senior Surgeon',
                'specialty' => 'Orthopedic & Soft Tissue Surgery',
                'license_number' => 'VET-SURG-8810',
                'is_available' => true,
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.role', 'doctor')
            ->assertJsonPath('data.profile.specialty', 'Orthopedic & Soft Tissue Surgery')
            ->assertJsonPath('data.capabilities.is_doctor', true)
            ->assertJsonPath('data.capabilities.can_perform_surgery', true)
            ->assertJsonPath('data.capabilities.can_prescribe_medication', true)
            ->assertJsonPath('data.capabilities.can_witness_controlled_substances', true);
    }

    public function test_clinic_can_create_assistant_and_counter_roles(): void
    {
        $loginResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/login', [
                'email' => 'john.miller@alphavet.com',
                'password' => 'password123',
            ]);

        $token = $loginResponse->json('token');

        // Create Assistant
        $assistantEmail = 'assistant.'.uniqid().'@alphavet.com';
        $assistantResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/staff', [
                'name' => 'Sam Assistant',
                'email' => $assistantEmail,
                'password' => 'password123',
                'role' => StaffRole::Assistant->value,
                'phone' => '+1-555-3311',
                'pin' => '3344',
            ]);

        $assistantResponse->assertStatus(201)
            ->assertJsonPath('data.role', 'assistant')
            ->assertJsonPath('data.capabilities.is_assistant', true)
            ->assertJsonPath('data.capabilities.can_witness_controlled_substances', true)
            ->assertJsonPath('data.capabilities.can_perform_surgery', false);

        // Create Counter / Receptionist / Accountant
        $counterEmail = 'counter.'.uniqid().'@alphavet.com';
        $counterResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/staff', [
                'name' => 'Emma Counter',
                'email' => $counterEmail,
                'password' => 'password123',
                'role' => StaffRole::Counter->value,
                'phone' => '+1-555-5599',
            ]);

        $counterResponse->assertStatus(201)
            ->assertJsonPath('data.role', 'counter')
            ->assertJsonPath('data.capabilities.is_counter', true)
            ->assertJsonPath('data.capabilities.can_manage_billing', true)
            ->assertJsonPath('data.capabilities.can_witness_controlled_substances', false);
    }

    public function test_full_staff_crud_lifecycle_list_show_update_status_and_delete(): void
    {
        $loginResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/login', [
                'email' => 'john.miller@alphavet.com',
                'password' => 'password123',
            ]);

        $token = $loginResponse->json('token');
        $alphaTenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();

        // 1. Create a staff member to manage
        $tempEmail = 'temp.vet.'.uniqid().'@alphavet.com';
        $createResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/staff', [
                'name' => 'Dr. Temporary',
                'email' => $tempEmail,
                'password' => 'password123',
                'role' => StaffRole::Doctor->value,
                'specialty' => 'General Practice',
            ]);

        $createResponse->assertStatus(201);
        $userId = $createResponse->json('data.id');

        // 2. List with role filter
        $listResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/staff?role=doctor');

        $listResponse->assertStatus(200)
            ->assertJsonFragment(['email' => $tempEmail]);

        // 3. Show staff details
        $showResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/staff/{$userId}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $userId)
            ->assertJsonPath('data.profile.specialty', 'General Practice');

        // 4. Update staff details and specialty
        $updateResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/staff/{$userId}", [
                'name' => 'Dr. Temporary Promoted',
                'email' => $tempEmail,
                'role' => StaffRole::Doctor->value,
                'specialty' => 'Cardiology & Ultrasound',
                'job_title' => 'Lead Cardiologist',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Dr. Temporary Promoted')
            ->assertJsonPath('data.profile.specialty', 'Cardiology & Ultrasound');

        // 5. Deactivate staff member
        $statusResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/staff/{$userId}/status", [
                'is_active' => false,
            ]);

        $statusResponse->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // 6. Delete staff member and confirm removal from tenant DB and landlord lookup
        $deleteResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/staff/{$userId}");

        $deleteResponse->assertStatus(200)
            ->assertJson(['message' => 'Staff member deleted successfully.']);

        $this->assertDatabaseMissing('tenant_staff_lookup', [
            'email' => $tempEmail,
            'tenant_id' => $alphaTenant->id,
        ], 'landlord');
    }
}
