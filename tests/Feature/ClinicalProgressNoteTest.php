<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClinicalCase;
use App\Models\ClinicalProgressNote;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Tests\TestCase;

class ClinicalProgressNoteTest extends TestCase
{
    private string $token;

    private Tenant $tenant;

    private User $veterinarian;

    private ClinicalCase $case;

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

        // Set up tenant context and fixtures
        $this->tenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $this->veterinarian = User::query()->where('email', 'john.miller@alphavet.com')->firstOrFail();

        $this->patient = Patient::factory()->create([
            'current_weight_kg' => '25.00',
        ]);

        $this->case = ClinicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
        ]);

        $tenantManager->reset();
    }

    public function test_can_record_clinical_progress_note_with_vitals_and_soap(): void
    {
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/cases/{$this->case->id}/progress-notes", [
                'temperature_c' => 38.6,
                'heart_rate_bpm' => 95,
                'respiratory_rate' => 24,
                'systolic_bp' => 125,
                'diastolic_bp' => 80,
                'mucous_membrane' => 'Pink and moist, CRT < 2s',
                'weight_kg' => 26.50,
                'subjective_notes' => 'Patient returned for Day 3 post-op wound evaluation. Eating well.',
                'objective_notes' => 'Surgical incision clean and dry, no erythema or discharge. Normothermic.',
                'assessment_notes' => 'Post-operative recovery proceeding as expected. Incision healing well.',
                'plan_notes' => 'Continue Carprofen 50mg PO BID for 3 more days. Suture removal in 7 days.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.clinical_case_id', $this->case->id)
            ->assertJsonPath('data.vet_id', $this->veterinarian->id)
            ->assertJsonPath('data.temperature_c', 38.6)
            ->assertJsonPath('data.heart_rate_bpm', 95)
            ->assertJsonPath('data.respiratory_rate', 24)
            ->assertJsonPath('data.systolic_bp', 125)
            ->assertJsonPath('data.diastolic_bp', 80)
            ->assertJsonPath('data.mucous_membrane', 'Pink and moist, CRT < 2s')
            ->assertJsonPath('data.weight_kg', 26.5)
            ->assertJsonPath('data.subjective_notes', 'Patient returned for Day 3 post-op wound evaluation. Eating well.')
            ->assertJsonPath('data.is_signed', false)
            ->assertJsonPath('data.signed_at', null);

        // Verify patient weight was updated
        app(TenantManager::class)->switch($this->tenant);
        $this->patient->refresh();
        $this->assertEquals(26.5, (float) $this->patient->current_weight_kg);
        app(TenantManager::class)->reset();
    }

    public function test_can_attach_and_retrieve_images_on_progress_note(): void
    {
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/cases/{$this->case->id}/progress-notes", [
                'subjective_notes' => 'Ultrasound follow-up scan for cystitis assessment.',
                'images' => [
                    [
                        'url' => 'https://storage.vetmanager.io/tenants/images/ultrasound-bladder-day3.jpg',
                        'title' => 'Transverse Bladder Wall Scan',
                        'mime_type' => 'image/jpeg',
                        'file_size_bytes' => 1250000,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonCount(1, 'data.images')
            ->assertJsonPath('data.images.0.title', 'Transverse Bladder Wall Scan')
            ->assertJsonPath('data.images.0.url', 'https://storage.vetmanager.io/tenants/images/ultrasound-bladder-day3.jpg');
    }

    public function test_can_list_progress_notes_for_clinical_case_newest_first(): void
    {
        app(TenantManager::class)->switch($this->tenant);

        ClinicalProgressNote::factory()->create([
            'clinical_case_id' => $this->case->id,
            'vet_id' => $this->veterinarian->id,
            'subjective_notes' => 'Visit 1 Notes',
            'created_at' => now()->subDays(2),
        ]);

        ClinicalProgressNote::factory()->create([
            'clinical_case_id' => $this->case->id,
            'vet_id' => $this->veterinarian->id,
            'subjective_notes' => 'Visit 2 Notes',
            'created_at' => now()->subDay(),
        ]);

        app(TenantManager::class)->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/cases/{$this->case->id}/progress-notes");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.subjective_notes', 'Visit 2 Notes')
            ->assertJsonPath('data.1.subjective_notes', 'Visit 1 Notes');
    }

    public function test_can_view_single_progress_note(): void
    {
        app(TenantManager::class)->switch($this->tenant);

        $note = ClinicalProgressNote::factory()->create([
            'clinical_case_id' => $this->case->id,
            'vet_id' => $this->veterinarian->id,
            'heart_rate_bpm' => 110,
        ]);

        app(TenantManager::class)->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/cases/{$this->case->id}/progress-notes/{$note->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $note->id)
            ->assertJsonPath('data.heart_rate_bpm', 110);
    }

    public function test_can_digitally_sign_progress_note(): void
    {
        app(TenantManager::class)->switch($this->tenant);

        $note = ClinicalProgressNote::factory()->create([
            'clinical_case_id' => $this->case->id,
            'vet_id' => $this->veterinarian->id,
            'is_signed' => false,
            'signed_at' => null,
        ]);

        app(TenantManager::class)->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/cases/{$this->case->id}/progress-notes/{$note->id}/sign");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $note->id)
            ->assertJsonPath('data.is_signed', true);

        $this->assertNotNull($response->json('data.signed_at'));
    }

    public function test_cannot_sign_already_signed_progress_note(): void
    {
        app(TenantManager::class)->switch($this->tenant);

        $note = ClinicalProgressNote::factory()->signed()->create([
            'clinical_case_id' => $this->case->id,
            'vet_id' => $this->veterinarian->id,
        ]);

        app(TenantManager::class)->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/cases/{$this->case->id}/progress-notes/{$note->id}/sign");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['progress_note']);
    }

    public function test_clinical_case_has_many_progress_notes_relationship_and_no_direct_images_relationship(): void
    {
        app(TenantManager::class)->switch($this->tenant);

        $note = ClinicalProgressNote::factory()->create([
            'clinical_case_id' => $this->case->id,
            'vet_id' => $this->veterinarian->id,
        ]);

        $this->assertTrue($this->case->progressNotes()->exists());
        $this->assertEquals($note->id, $this->case->progressNotes->first()->id);

        // Verify ClinicalCase does not define direct images() relationship
        $this->assertFalse(method_exists($this->case, 'images'));
        $this->assertFalse(method_exists($this->case, 'image'));

        // Verify images belong to ClinicalProgressNote
        $this->assertTrue(method_exists($note, 'images'));
        $this->assertTrue(method_exists($note, 'image'));

        app(TenantManager::class)->reset();
    }
}
