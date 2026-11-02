<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ClientType;
use App\Enums\PatientStatus;
use App\Models\Client;
use App\Models\Patient;
use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    private string $token;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TenantSeeder::class);
        $this->seed(TenantStaffSeeder::class);

        // Authenticate as Dr. John Miller on AlphaPet
        $loginResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/login', [
                'email' => 'john.miller@alphavet.com',
                'password' => 'password123',
            ]);

        $this->token = $loginResponse->json('token');
        $this->tenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
    }

    public function test_authenticated_staff_can_list_clients_with_pagination_and_search(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        Client::factory()->create([
            'name' => 'Wickstead Kennels',
            'phone_primary' => '+15550011',
            'contact_person' => 'Arthur Dent',
        ]);
        Client::factory()->create([
            'name' => 'Oakwood Equestrian Center',
            'phone_primary' => '+15550022',
            'contact_person' => 'Ford Prefect',
        ]);

        $tenantManager->reset();

        // 1. List clients without filters
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/clients');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'client_type',
                        'client_type_label',
                        'name',
                        'contact_person',
                        'phone_primary',
                        'active_patients_count',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        // 2. Search by client name
        $searchResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/clients?search=Wickstead');

        $searchResponse->assertStatus(200)
            ->assertJsonFragment(['name' => 'Wickstead Kennels'])
            ->assertJsonMissing(['name' => 'Oakwood Equestrian Center']);
    }

    public function test_staff_can_filter_clients_by_type(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        Client::factory()->individual()->create();
        Client::factory()->farm()->create();

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/clients?client_type=farm_manager');

        $response->assertStatus(200);
        $items = $response->json('data');
        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $this->assertSame(ClientType::FarmManager->value, $item['client_type']);
        }

        $indivResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/clients?client_type=individual');

        $indivResponse->assertStatus(200);
        $indivItems = $indivResponse->json('data');
        $this->assertNotEmpty($indivItems);
        foreach ($indivItems as $item) {
            $this->assertSame(ClientType::Individual->value, $item['client_type']);
        }
    }

    public function test_staff_can_create_individual_and_farm_clients(): void
    {
        $payload = [
            'client_type' => ClientType::Individual->value,
            'name' => 'Bruce Wayne',
            'phone_primary' => '+1555888999',
            'contact_person' => null,
            'phone_secondary' => '+1555888000',
            'physical_address' => '1007 Mountain Drive, Gotham',
            'notes' => 'Prefers morning appointments.',
        ];

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/clients', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Bruce Wayne')
            ->assertJsonPath('data.client_type', ClientType::Individual->value)
            ->assertJsonPath('data.phone_primary', '+1555888999')
            ->assertJsonPath('data.physical_address', '1007 Mountain Drive, Gotham');

        $clientId = $response->json('data.id');

        app(TenantManager::class)->switch($this->tenant);
        $this->assertDatabaseHas('clients', [
            'id' => $clientId,
            'name' => 'Bruce Wayne',
            'phone_primary' => '+1555888999',
        ], 'tenant');
        app(TenantManager::class)->reset();
    }

    public function test_create_client_validation_fails_with_missing_required_fields(): void
    {
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/clients', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client_type', 'name', 'phone_primary']);
    }

    public function test_staff_can_view_single_client_with_active_patients_count(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = Client::factory()->create([
            'name' => 'Diana Prince',
            'phone_primary' => '+1555777666',
        ]);

        // 2 active patients, 1 deceased patient
        Patient::factory()->create([
            'client_id' => $client->id,
            'status' => PatientStatus::Active,
        ]);
        Patient::factory()->create([
            'client_id' => $client->id,
            'status' => PatientStatus::Active,
        ]);
        Patient::factory()->create([
            'client_id' => $client->id,
            'status' => PatientStatus::Deceased,
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/clients/{$client->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('data.name', 'Diana Prince')
            ->assertJsonPath('data.active_patients_count', 2);
    }

    public function test_staff_can_update_client_details(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = Client::factory()->create([
            'client_type' => ClientType::Individual,
            'name' => 'Clark Kent',
            'phone_primary' => '+1555111222',
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/clients/{$client->id}", [
                'client_type' => ClientType::Individual->value,
                'name' => 'Clark Joseph Kent',
                'phone_primary' => '+1555999888',
                'contact_person' => 'Lois Lane',
                'physical_address' => '344 Clinton St, Metropolis',
                'notes' => 'Updated address and contact.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Clark Joseph Kent')
            ->assertJsonPath('data.phone_primary', '+1555999888')
            ->assertJsonPath('data.contact_person', 'Lois Lane');

        $tenantManager->switch($this->tenant);
        $client->refresh();
        $this->assertSame('Clark Joseph Kent', $client->name);
        $this->assertSame('+1555999888', $client->phone_primary);
        $tenantManager->reset();
    }

    public function test_client_deletion_is_blocked_when_active_patients_exist(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = Client::factory()->create();

        Patient::factory()->create([
            'client_id' => $client->id,
            'status' => PatientStatus::Active,
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/clients/{$client->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client']);

        $tenantManager->switch($this->tenant);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'deleted_at' => null,
        ], 'tenant');
        $tenantManager->reset();
    }

    public function test_staff_can_soft_delete_client_without_active_patients(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = Client::factory()->create();

        // Has only an archived patient, no active ones
        Patient::factory()->create([
            'client_id' => $client->id,
            'status' => PatientStatus::Archived,
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/clients/{$client->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Client record archived successfully.']);

        $tenantManager->switch($this->tenant);
        $this->assertSoftDeleted('clients', [
            'id' => $client->id,
        ], 'tenant');
        $tenantManager->reset();
    }

    public function test_unauthenticated_and_cross_tenant_access_is_rejected(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);
        $client = Client::factory()->create();
        $tenantManager->reset();

        // 1. Unauthenticated request without token
        $unauthResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->getJson("/api/v1/clients/{$client->id}");

        $unauthResponse->assertStatus(401);

        // 2. Token used against a different tenant is rejected as Unauthenticated (401)
        $crossTenantResponse = $this->withHeader('X-Tenant-ID', 'equinecare')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/clients');

        $crossTenantResponse->assertStatus(401);
    }
}
