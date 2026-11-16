<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClinicalCase;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Tests\TestCase;

class VisitTest extends TestCase
{
    private string $token;

    private Tenant $tenant;

    private User $veterinarian;

    private ClinicalCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TenantSeeder::class);
        $this->seed(TenantStaffSeeder::class);

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

        $patient = Patient::factory()->create();
        $this->case = ClinicalCase::factory()->create([
            'patient_id' => $patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
        ]);

        $tenantManager->reset();
    }

    public function test_can_list_visits_for_case(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        Visit::create([
            'case_id' => $this->case->id,
            'title' => 'Initial Consultation',
            'visit_type' => 'outpatient',
            'status' => 'open',
            'visit_date' => now(),
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/cases/{$this->case->id}/visits");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'case_id',
                        'title',
                        'visit_type',
                        'status',
                        'is_signed',
                        'visit_date',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Initial Consultation', $response->json('data.0.title'));
    }

    public function test_can_create_new_visit_for_case(): void
    {
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/cases/{$this->case->id}/visits", [
                'title' => 'Follow-up Day 3',
                'visit_type' => 'outpatient',
                'reason_for_visit' => 'Re-check temperature and wound healing',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Follow-up Day 3')
            ->assertJsonPath('data.case_id', $this->case->id)
            ->assertJsonPath('data.reason_for_visit', 'Re-check temperature and wound healing')
            ->assertJsonPath('data.is_signed', false);

        $visitId = $response->json('data.id');

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $this->assertDatabaseHas('visits', [
            'id' => $visitId,
            'case_id' => $this->case->id,
            'title' => 'Follow-up Day 3',
        ], 'tenant');

        $tenantManager->reset();
    }

    public function test_can_update_visit(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $visit = Visit::create([
            'case_id' => $this->case->id,
            'title' => 'Initial Consult',
            'visit_type' => 'outpatient',
            'status' => 'open',
            'visit_date' => now(),
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/visits/{$visit->id}", [
                'title' => 'Updated Consult Title',
                'reason_for_visit' => 'Updated reason',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Consult Title')
            ->assertJsonPath('data.reason_for_visit', 'Updated reason');
    }

    public function test_can_show_visit_details(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $visit = Visit::create([
            'case_id' => $this->case->id,
            'title' => 'Emergency Triage',
            'visit_type' => 'emergency',
            'status' => 'open',
            'visit_date' => now(),
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/visits/{$visit->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $visit->id)
            ->assertJsonPath('data.title', 'Emergency Triage')
            ->assertJsonPath('data.visit_type', 'emergency');
    }

    public function test_client_cannot_forge_signed_by_or_signed_at_on_visit(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $visit = Visit::create([
            'case_id' => $this->case->id,
            'title' => 'Pre-sign Consult',
            'visit_type' => 'outpatient',
            'status' => 'open',
            'is_signed' => false,
            'visit_date' => now(),
        ]);

        $tenantManager->reset();

        $forgedClinician = '00000000-0000-0000-0000-000000000099';
        $forgedDate = '2020-01-01T00:00:00Z';

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/visits/{$visit->id}", [
                'is_signed' => true,
                'signed_by' => $forgedClinician,
                'signed_at' => $forgedDate,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_signed', true);

        $tenantManager->switch($this->tenant);
        $reloaded = Visit::findOrFail($visit->id);
        $tenantManager->reset();

        $this->assertTrue($reloaded->is_signed);
        // Ensure server securely assigned authenticated vet's ID and did NOT accept forged signature
        $this->assertEquals($this->veterinarian->id, $reloaded->signed_by);
        $this->assertNotEquals($forgedClinician, $reloaded->signed_by);
        $this->assertNotEquals('2020-01-01', $reloaded->signed_at->format('Y-m-d'));
    }

    public function test_cannot_update_visit_once_signed(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $visit = Visit::create([
            'case_id' => $this->case->id,
            'title' => 'Locked Visit',
            'visit_type' => 'outpatient',
            'status' => 'closed',
            'is_signed' => true,
            'signed_at' => now(),
            'signed_by' => $this->veterinarian->id,
            'visit_date' => now(),
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/visits/{$visit->id}", [
                'title' => 'Attempted Title Tampering',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['visit']);
    }
}
