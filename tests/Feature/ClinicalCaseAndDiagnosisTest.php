<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Enums\DiagnosisStatus;
use App\Models\Client;
use App\Models\ClinicalCase;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClinicalCaseAndDiagnosisTest extends TestCase
{
    private string $token;

    private Tenant $tenant;

    private User $veterinarian;

    private Patient $patient;

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

        $client = Client::factory()->create();
        $this->patient = Patient::factory()->create([
            'client_id' => $client->id,
            'name_or_tag' => 'Barnaby',
        ]);
        $tenantManager->reset();
    }

    public function test_staff_can_open_new_clinical_case_for_patient(): void
    {
        $payload = [
            'patient_id' => $this->patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
            'chief_complaint' => 'Acute lethargy, persistent vomiting for 24 hours.',
            'case_type' => CaseType::Inpatient->value,
            'priority' => CasePriority::Urgent->value,
            'provisional_diagnosis' => 'Suspected acute pancreatitis or foreign body obstruction.',
        ];

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/cases', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.patient_id', $this->patient->id)
            ->assertJsonPath('data.primary_veterinarian_id', $this->veterinarian->id)
            ->assertJsonPath('data.case_type', CaseType::Inpatient->value)
            ->assertJsonPath('data.status', CaseStatus::Open->value)
            ->assertJsonPath('data.priority', CasePriority::Urgent->value)
            ->assertJsonPath('data.chief_complaint', 'Acute lethargy, persistent vomiting for 24 hours.');

        $caseNumber = $response->json('data.case_number');
        $this->assertStringStartsWith('C-', $caseNumber);

        $caseId = $response->json('data.id');

        app(TenantManager::class)->switch($this->tenant);
        $this->assertDatabaseHas('cases', [
            'id' => $caseId,
            'case_number' => $caseNumber,
            'patient_id' => $this->patient->id,
            'status' => CaseStatus::Open->value,
        ], 'tenant');
        app(TenantManager::class)->reset();
    }

    public function test_open_case_validation_fails_with_invalid_patient_or_vet(): void
    {
        $randomUuid = (string) Str::uuid();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/cases', [
                'patient_id' => $randomUuid,
                'primary_veterinarian_id' => $randomUuid,
                'chief_complaint' => 'Checkup',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['patient_id', 'primary_veterinarian_id']);
    }

    public function test_staff_can_list_clinical_cases_for_patient_newest_first(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $olderCase = ClinicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
            'chief_complaint' => 'Older case: Minor ear infection',
            'admitted_at' => now()->subDays(10),
        ]);

        $newerCase = ClinicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
            'chief_complaint' => 'Newer case: Annual vaccination workup',
            'admitted_at' => now(),
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/patients/{$this->patient->id}/cases");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $data = $response->json('data');
        $this->assertSame($newerCase->id, $data[0]['id']);
        $this->assertSame($olderCase->id, $data[1]['id']);
    }

    public function test_staff_can_view_single_clinical_case_with_relations(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $case = ClinicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
            'chief_complaint' => 'Limping on right hind limb.',
        ]);

        Diagnosis::factory()->create([
            'patient_id' => $this->patient->id,
            'case_id' => $case->id,
            'user_id' => $this->veterinarian->id,
            'diagnosis' => 'Cranial cruciate ligament tear',
            'status' => DiagnosisStatus::Active,
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/cases/{$case->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $case->id)
            ->assertJsonPath('data.patient_id', $this->patient->id)
            ->assertJsonPath('data.primary_veterinarian_id', $this->veterinarian->id)
            ->assertJsonPath('data.primary_veterinarian.name', 'Dr. John Miller')
            ->assertJsonCount(1, 'data.diagnoses')
            ->assertJsonPath('data.diagnoses.0.diagnosis', 'Cranial cruciate ligament tear');
    }

    public function test_staff_can_update_case_status_to_discharged(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $case = ClinicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
            'status' => CaseStatus::Open,
            'discharged_at' => null,
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/cases/{$case->id}", [
                'primary_veterinarian_id' => $this->veterinarian->id,
                'chief_complaint' => $case->chief_complaint,
                'case_type' => CaseType::Inpatient->value,
                'status' => CaseStatus::Discharged->value,
                'priority' => CasePriority::Routine->value,
                'provisional_diagnosis' => 'Gastritis',
                'final_diagnosis' => 'Acute uncomplicated gastroenteritis, resolved.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', CaseStatus::Discharged->value)
            ->assertJsonPath('data.final_diagnosis', 'Acute uncomplicated gastroenteritis, resolved.');

        $this->assertNotNull($response->json('data.discharged_at'));

        $tenantManager->switch($this->tenant);
        $case->refresh();
        $this->assertSame(CaseStatus::Discharged, $case->status);
        $this->assertNotNull($case->discharged_at);
        $this->assertSame('Acute uncomplicated gastroenteritis, resolved.', $case->final_diagnosis);
        $tenantManager->reset();
    }

    public function test_staff_can_record_diagnosis_for_clinical_case(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $case = ClinicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/cases/{$case->id}/diagnoses", [
                'diagnosis' => 'Bovine Bronchopneumonia',
                'status' => DiagnosisStatus::Tentative->value,
                'description' => 'Auscultation revealed bilateral wheezing and crackles.',
                'notes' => 'Prescribed Florfenicol injectable.',
                'diagnosed_at' => now()->toDateTimeString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.case_id', $case->id)
            ->assertJsonPath('data.diagnosis', 'Bovine Bronchopneumonia')
            ->assertJsonPath('data.status', DiagnosisStatus::Tentative->value)
            ->assertJsonPath('data.recorded_by', $this->veterinarian->id);

        $diagnosisId = $response->json('data.id');

        app(TenantManager::class)->switch($this->tenant);
        $this->assertDatabaseHas('diagnoses', [
            'id' => $diagnosisId,
            'case_id' => $case->id,
            'diagnosis' => 'Bovine Bronchopneumonia',
            'user_id' => $this->veterinarian->id,
        ], 'tenant');
        app(TenantManager::class)->reset();
    }

    public function test_staff_can_list_diagnoses_for_clinical_case(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $case = ClinicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
        ]);

        Diagnosis::factory()->create([
            'patient_id' => $this->patient->id,
            'case_id' => $case->id,
            'user_id' => $this->veterinarian->id,
            'diagnosis' => 'Otitis Externa',
            'diagnosed_at' => now()->subDay(),
        ]);
        Diagnosis::factory()->create([
            'patient_id' => $this->patient->id,
            'case_id' => $case->id,
            'user_id' => $this->veterinarian->id,
            'diagnosis' => 'Atopic Dermatitis',
            'diagnosed_at' => now(),
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/cases/{$case->id}/diagnoses");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.diagnosis', 'Atopic Dermatitis')
            ->assertJsonPath('data.1.diagnosis', 'Otitis Externa');
    }
}
