<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CaseStatus;
use App\Enums\PatientGender;
use App\Enums\PatientStatus;
use App\Models\Client;
use App\Models\ClinicalCase;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Illuminate\Support\Str;
use Tests\TestCase;

class PatientManagementTest extends TestCase
{
    private string $token;

    private Tenant $tenant;

    private User $veterinarian;

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

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);
        $this->veterinarian = User::query()->where('email', 'john.miller@alphavet.com')->firstOrFail();
        $tenantManager->reset();
    }

    public function test_staff_can_list_patients_for_a_client(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = Client::factory()->create();

        $patientA = Patient::factory()->create([
            'client_id' => $client->id,
            'name_or_tag' => 'Bella',
        ]);
        $patientB = Patient::factory()->create([
            'client_id' => $client->id,
            'name_or_tag' => 'Max',
        ]);

        // Create an active clinical case for Bella
        ClinicalCase::factory()->create([
            'patient_id' => $patientA->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
            'status' => CaseStatus::Open,
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/clients/{$client->id}/patients");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name_or_tag',
                        'species',
                        'gender',
                        'status',
                        'active_cases_count',
                    ],
                ],
            ]);

        $items = collect($response->json('data'));
        $bella = $items->firstWhere('name_or_tag', 'Bella');
        $max = $items->firstWhere('name_or_tag', 'Max');

        $this->assertNotNull($bella);
        $this->assertNotNull($max);
        $this->assertSame(1, $bella['active_cases_count']);
        $this->assertSame(0, $max['active_cases_count']);
    }

    public function test_staff_can_register_new_patient(): void
    {
        $microchip = '985'.fake()->unique()->numerify('############');

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);
        $client = Client::factory()->create();
        $tenantManager->reset();

        $payload = [
            'client_id' => $client->id,
            'name_or_tag' => 'Milo',
            'species' => 'Canine',
            'breed' => 'Golden Retriever',
            'gender' => PatientGender::IntactMale->value,
            'age' => 3,
            'date_of_birth' => '2023-01-15',
            'is_approximate_age' => false,
            'microchip_number' => $microchip,
            'ear_tag_id' => null,
            'coat_color_markings' => 'Golden / Cream',
            'current_weight_kg' => 31.5,
            'history' => 'Up to date with vaccinations.',
            'notes' => 'Friendly disposition, food motivated.',
            'allergies' => ['Penicillin', 'Sulfa drugs'],
        ];

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/patients', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.client_id', $client->id)
            ->assertJsonPath('data.name_or_tag', 'Milo')
            ->assertJsonPath('data.species', 'Canine')
            ->assertJsonPath('data.breed', 'Golden Retriever')
            ->assertJsonPath('data.gender', PatientGender::IntactMale->value)
            ->assertJsonPath('data.microchip_number', $microchip)
            ->assertJsonPath('data.allergies', ['Penicillin', 'Sulfa drugs']);

        $patientId = $response->json('data.id');

        app(TenantManager::class)->switch($this->tenant);
        $this->assertDatabaseHas('patients', [
            'id' => $patientId,
            'name_or_tag' => 'Milo',
            'microchip_number' => $microchip,
        ], 'tenant');
        app(TenantManager::class)->reset();
    }

    public function test_patient_registration_fails_with_duplicate_microchip(): void
    {
        $microchip = '985'.fake()->unique()->numerify('############');

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);
        $client = Client::factory()->create();
        Patient::factory()->create([
            'client_id' => $client->id,
            'microchip_number' => $microchip,
        ]);
        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/patients', [
                'client_id' => $client->id,
                'name_or_tag' => 'Duplicate Chip Pet',
                'species' => 'Feline',
                'gender' => PatientGender::SpayedFemale->value,
                'microchip_number' => $microchip,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['microchip_number']);
    }

    public function test_patient_registration_fails_when_client_does_not_exist(): void
    {
        $nonExistentUuid = (string) Str::uuid();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/patients', [
                'client_id' => $nonExistentUuid,
                'name_or_tag' => 'Ghost Pet',
                'species' => 'Canine',
                'gender' => PatientGender::NeuteredMale->value,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client_id']);
    }

    public function test_staff_can_view_single_patient_with_client_loaded(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = Client::factory()->create(['name' => 'Sarah Walker']);
        $patient = Patient::factory()->create([
            'client_id' => $client->id,
            'name_or_tag' => 'Shadow',
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/patients/{$patient->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $patient->id)
            ->assertJsonPath('data.name_or_tag', 'Shadow')
            ->assertJsonPath('data.client.id', $client->id)
            ->assertJsonPath('data.client.name', 'Sarah Walker');
    }

    public function test_staff_can_update_patient_details_and_mark_as_deceased(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = Client::factory()->create();
        $patient = Patient::factory()->create([
            'client_id' => $client->id,
            'status' => PatientStatus::Active,
            'current_weight_kg' => '22.000',
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/patients/{$patient->id}", [
                'name_or_tag' => $patient->name_or_tag,
                'species' => $patient->species,
                'gender' => $patient->gender->value,
                'status' => PatientStatus::Deceased->value,
                'current_weight_kg' => 24.500,
                'notes' => 'Passed peacefully.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', PatientStatus::Deceased->value)
            ->assertJsonPath('data.current_weight_kg', '24.500');

        $this->assertNotNull($response->json('data.deceased_at'));

        $tenantManager->switch($this->tenant);
        $patient->refresh();
        $this->assertSame(PatientStatus::Deceased, $patient->status);
        $this->assertNotNull($patient->deceased_at);
        $this->assertEquals(24.5, (float) $patient->current_weight_kg);
        $this->assertNotNull($patient->weight_recorded_at);
        $tenantManager->reset();
    }

    public function test_updating_patient_fails_with_duplicate_microchip_of_another_patient(): void
    {
        $microchipA = '985'.fake()->unique()->numerify('############');
        $microchipB = '985'.fake()->unique()->numerify('############');

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = Client::factory()->create();
        $patientA = Patient::factory()->create([
            'client_id' => $client->id,
            'microchip_number' => $microchipA,
        ]);
        $patientB = Patient::factory()->create([
            'client_id' => $client->id,
            'microchip_number' => $microchipB,
        ]);

        $tenantManager->reset();

        // 1. Updating Patient B to use Patient A's microchip fails with 422
        $failResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/patients/{$patientB->id}", [
                'name_or_tag' => $patientB->name_or_tag,
                'species' => $patientB->species,
                'gender' => $patientB->gender->value,
                'status' => $patientB->status->value,
                'microchip_number' => $microchipA,
            ]);

        $failResponse->assertStatus(422)
            ->assertJsonValidationErrors(['microchip_number']);

        // 2. Updating Patient B keeping its own microchip succeeds
        $successResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/patients/{$patientB->id}", [
                'name_or_tag' => 'Updated Max',
                'species' => $patientB->species,
                'gender' => $patientB->gender->value,
                'status' => $patientB->status->value,
                'microchip_number' => $microchipB,
            ]);

        $successResponse->assertStatus(200)
            ->assertJsonPath('data.name_or_tag', 'Updated Max');
    }
}
