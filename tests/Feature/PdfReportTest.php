<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClinicalCase;
use App\Models\ClinicalProgressNote;
use App\Models\DoseAdministration;
use App\Models\InventoryProduct;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Illuminate\Support\Str;
use Tests\TestCase;

class PdfReportTest extends TestCase
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

        $this->patient = Patient::factory()->create([
            'name_or_tag' => 'Thunder & <Storm>',
            'species' => 'Equine',
            'breed' => 'Arabian',
            'current_weight_kg' => 450.50,
            'microchip_number' => (string) fake()->unique()->numerify('985141##########'),
        ]);

        $this->case = ClinicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
            'chief_complaint' => 'Right forelimb lameness after jump routine',
            'provisional_diagnosis' => 'Suspected SDFT strain',
        ]);

        $tenantManager->reset();
    }

    public function test_can_generate_pdf_report_for_single_visit(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $visit = Visit::create([
            'case_id' => $this->case->id,
            'title' => 'Diagnostic Examination & Flexion',
            'visit_type' => 'outpatient',
            'status' => 'completed',
            'is_signed' => true,
            'signed_at' => now(),
            'signed_by' => 'Dr. John Miller, DVM',
            'reason_for_visit' => 'Full dynamic exam and ultrasound scan',
            'visit_date' => now(),
        ]);

        // Add SOAP Progress Note with Vitals
        ClinicalProgressNote::create([
            'clinical_case_id' => $this->case->id,
            'visit_id' => $visit->id,
            'vet_id' => $this->veterinarian->id,
            'temperature_c' => 38.1,
            'heart_rate_bpm' => 36,
            'respiratory_rate' => 14,
            'systolic_bp' => 120,
            'diastolic_bp' => 78,
            'mucous_membrane' => 'Pink & Moist',
            'weight_kg' => 450.50,
            'subjective_notes' => 'Horse was warm-up trained, trainer noted mild stumble.',
            'objective_notes' => 'Palpation revealed mild effusion over right carpus. AAEP Lameness Grade 2/5.',
            'assessment_notes' => 'Right forelimb superficial flexor tendonitis.',
            'plan_notes' => 'Cold hosing BID, stall rest for 14 days, follow-up ultrasound next week.',
            'is_signed' => true,
            'signed_at' => now(),
        ]);

        // Add Product & Dose Administration
        $product = InventoryProduct::create([
            'id' => (string) Str::uuid(),
            'sku' => 'FLUNIXIN-'.Str::random(5),
            'name' => 'Flunixin Meglumine 50mg/ml',
            'form' => 'liquid_injectable',
            'track_batches' => true,
        ]);

        DoseAdministration::create([
            'id' => (string) Str::uuid(),
            'case_id' => $this->case->id,
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
            'administered_by_user_id' => $this->veterinarian->id,
            'product_id' => $product->id,
            'dose_amount_base_units' => '10.0000',
            'route_administered' => 'IV',
            'clinical_notes' => 'Administered slowly via IV catheter',
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->get("/api/v1/visits/{$visit->id}/pdf");

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('nosniff', (string) $response->headers->get('X-Content-Type-Options'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_can_generate_comprehensive_case_dossier_pdf_with_multiple_visits(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        // Visit 1: Initial Exam
        $visit1 = Visit::create([
            'case_id' => $this->case->id,
            'title' => 'Initial Triage & Ultrasound',
            'visit_type' => 'emergency',
            'status' => 'completed',
            'is_signed' => true,
            'signed_at' => now()->subDays(5),
            'signed_by' => 'Dr. John Miller, DVM',
            'reason_for_visit' => 'Acute lameness',
            'visit_date' => now()->subDays(5),
        ]);

        ClinicalProgressNote::create([
            'clinical_case_id' => $this->case->id,
            'visit_id' => $visit1->id,
            'vet_id' => $this->veterinarian->id,
            'temperature_c' => 38.4,
            'heart_rate_bpm' => 44,
            'respiratory_rate' => 18,
            'subjective_notes' => 'Severe acute lameness reported by trainer.',
            'objective_notes' => 'Grade 3/5 lameness. Palpation tender.',
            'assessment_notes' => 'Tendon strain suspected.',
            'plan_notes' => 'Apply support bandage, prescribe NSAIDs.',
        ]);

        // Visit 2: Follow-up Checkup
        $visit2 = Visit::create([
            'case_id' => $this->case->id,
            'title' => 'Day 5 Re-assessment',
            'visit_type' => 'outpatient',
            'status' => 'completed',
            'is_signed' => true,
            'signed_at' => now(),
            'signed_by' => 'Dr. John Miller, DVM',
            'reason_for_visit' => 'Progress check',
            'visit_date' => now(),
        ]);

        ClinicalProgressNote::create([
            'clinical_case_id' => $this->case->id,
            'visit_id' => $visit2->id,
            'vet_id' => $this->veterinarian->id,
            'temperature_c' => 37.9,
            'heart_rate_bpm' => 34,
            'respiratory_rate' => 12,
            'subjective_notes' => 'Horse is much more comfortable in stall.',
            'objective_notes' => 'Swelling significantly reduced. Grade 1/5 lameness.',
            'assessment_notes' => 'Positive healing response.',
            'plan_notes' => 'Hand walking 15 mins daily.',
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->get("/api/v1/cases/{$this->case->id}/pdf");

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('inline;', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('clinical-case-', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_can_download_case_pdf_with_download_flag(): void
    {
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->get("/api/v1/cases/{$this->case->id}/pdf?download=1");

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_pdf_endpoints_require_authentication(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $visit = Visit::create([
            'case_id' => $this->case->id,
            'title' => 'Auth Check Visit',
            'visit_type' => 'outpatient',
            'status' => 'open',
            'visit_date' => now(),
        ]);

        $tenantManager->reset();

        $response1 = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->getJson("/api/v1/cases/{$this->case->id}/pdf");
        $response1->assertStatus(401);

        $response2 = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->getJson("/api/v1/visits/{$visit->id}/pdf");
        $response2->assertStatus(401);
    }

    public function test_pdf_returns_404_for_non_existent_resources(): void
    {
        $nonExistentId = (string) Str::uuid();

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/cases/{$nonExistentId}/pdf")
            ->assertStatus(404);

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/visits/{$nonExistentId}/pdf")
            ->assertStatus(404);
    }

    public function test_cannot_access_case_or_visit_pdf_from_another_tenant(): void
    {
        // 1. Login as betavet doctor
        $betaLoginResponse = $this->withHeader('X-Tenant-ID', 'betavet')
            ->postJson('/api/v1/auth/login', [
                'email' => 'sarah.connor@betavet.com',
                'password' => 'password123',
            ]);

        $betaToken = $betaLoginResponse->json('token');

        // 2. Attempt to fetch alphavet case using betavet credentials
        $response = $this->withHeader('X-Tenant-ID', 'betavet')
            ->withHeader('Authorization', "Bearer {$betaToken}")
            ->getJson("/api/v1/cases/{$this->case->id}/pdf");

        // Must fail with 404 under tenant isolation
        $response->assertStatus(404);
    }
}
