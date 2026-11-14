<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\CaseType;
use App\Enums\MeasurementBaseUnit;
use App\Enums\PatientGender;
use App\Models\Client;
use App\Models\ClinicalCase;
use App\Models\InventoryProduct;
use App\Models\Patient;
use App\Models\Species;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClinicWebPortalTest extends TestCase
{
    private Tenant $alphaTenant;

    private Tenant $equineTenant;

    private User $doctor;

    private Client $client;

    private Patient $patient;

    private ClinicalCase $case;

    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('s3');

        $this->seed(TenantSeeder::class);
        $this->seed(TenantStaffSeeder::class);

        $this->alphaTenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        $this->equineTenant = Tenant::query()->where('slug', 'equinecare')->firstOrFail();

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->alphaTenant);

        $this->doctor = User::query()->where('email', 'john.miller@alphavet.com')->firstOrFail();

        // Create clinical baseline in tenant DB
        $this->client = Client::query()->create([
            'name' => 'Alice Walker',
            'client_type' => 'individual',
            'phone_primary' => '+15551234567',
        ]);

        $this->patient = Patient::query()->create([
            'client_id' => $this->client->id,
            'name_or_tag' => 'Barnaby',
            'species' => 'Canine',
            'breed' => 'Golden Retriever',
            'gender' => PatientGender::NeuteredMale,
            'age' => 4,
            'current_weight_kg' => '32.50',
            'allergies' => ['Penicillin', 'Meloxicam'],
            'status' => 'active',
        ]);

        $this->case = ClinicalCase::query()->create([
            'case_number' => 'CAS-TEST-'.strtoupper(bin2hex(random_bytes(4))),
            'patient_id' => $this->patient->id,
            'primary_veterinarian_id' => $this->doctor->id,
            'case_type' => CaseType::Outpatient,
            'status' => CaseStatus::Open,
            'priority' => CasePriority::Urgent,
            'chief_complaint' => 'Acute onset of vomiting and severe lethargy.',
            'provisional_diagnosis' => 'Acute Gastritis',
            'admitted_at' => now(),
        ]);

        $this->visit = Visit::query()->create([
            'case_id' => $this->case->id,
            'title' => 'Day 1 Physical Exam & Vitals',
            'visit_type' => 'Initial Exam',
            'status' => 'in_progress',
            'is_signed' => false,
            'reason_for_visit' => 'Initial assessment and stabilization',
            'visit_date' => now(),
        ]);

        // Seed Dynamic Species if not present
        if (! Species::query()->where('name', 'Canine')->exists()) {
            Species::query()->create([
                'name' => 'Canine',
                'code' => 'CAN',
                'icon' => 'dog',
                'is_system_default' => true,
                'is_active' => true,
            ]);
        }

        $tenantManager->reset();
    }

    public function test_guest_visiting_portal_is_redirected_to_portal_login(): void
    {
        $response = $this->get('/portal/dashboard');

        $response->assertRedirect('/portal/login');
    }

    public function test_root_and_login_shortcuts_redirect_to_portal(): void
    {
        $this->get('/')->assertRedirect(route('portal.dashboard'));
        $this->get('/login')->assertRedirect(route('portal.login'));
    }

    public function test_two_step_web_login_flow(): void
    {
        // Step 1: Discover email with single clinic association
        $discoverResponse = $this->post('/portal/login/discover', [
            'email' => 'john.miller@alphavet.com',
        ]);

        $discoverResponse->assertStatus(200);
        $discoverResponse->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->where('step', 'enter_password')
            ->has('selectedTenant')
        );

        // Step 2: Authenticate into tenant database with password
        $authResponse = $this->post('/portal/login/authenticate', [
            'email' => 'john.miller@alphavet.com',
            'password' => 'password123',
            'tenant_id' => $this->alphaTenant->id,
        ]);

        $authResponse->assertRedirect(route('portal.dashboard'));
        $this->assertEquals($this->alphaTenant->id, session('active_tenant_id'));
    }

    public function test_multi_clinic_discovery_and_selection(): void
    {
        // User associated with both AlphaVet and EquineCare
        $discoverResponse = $this->post('/portal/login/discover', [
            'email' => 'sarah.connor@vetservices.com',
        ]);

        $discoverResponse->assertStatus(200);
        $discoverResponse->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->where('step', 'select_clinic')
        );

        // Select EquineCare
        $selectResponse = $this->post('/portal/login/select-clinic', [
            'email' => 'sarah.connor@vetservices.com',
            'tenant_id' => $this->equineTenant->id,
        ]);

        $selectResponse->assertStatus(200);
        $selectResponse->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->where('step', 'enter_password')
            ->where('selectedTenant.id', $this->equineTenant->id)
        );
    }

    public function test_multi_clinic_switcher(): void
    {
        // Login as sarah in AlphaVet
        $this->post('/portal/login/authenticate', [
            'email' => 'sarah.connor@vetservices.com',
            'password' => 'password123',
            'tenant_id' => $this->alphaTenant->id,
        ])->assertRedirect(route('portal.dashboard'));

        $this->assertEquals($this->alphaTenant->id, session('active_tenant_id'));

        // Switch to EquineCare
        $switchResponse = $this->post("/portal/switch-clinic/{$this->equineTenant->id}");

        $switchResponse->assertRedirect(route('portal.dashboard'));
        $this->assertEquals($this->equineTenant->id, session('active_tenant_id'));
    }

    public function test_level_1_patient_profile_shows_cases_only_and_no_visits(): void
    {
        $this->withSession(['active_tenant_id' => $this->alphaTenant->id])
            ->actingAs($this->doctor, 'web');

        $response = $this->get("/portal/patients/{$this->patient->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Patients/Show')
            ->has('patient')
            ->where('patient.name_or_tag', 'Barnaby')
            ->where('patient.high_risk_allergies', 'Penicillin, Meloxicam')
            ->has('cases', 1)
            ->where('cases.0.case_number', $this->case->case_number)
            ->missing('visits')
            ->missing('mediaImages')
        );
    }

    public function test_patient_registration_and_update(): void
    {
        $this->withSession(['active_tenant_id' => $this->alphaTenant->id])
            ->actingAs($this->doctor, 'web');

        // Create new patient
        $storeResponse = $this->post('/portal/patients', [
            'client_id' => $this->client->id,
            'name_or_tag' => 'Luna',
            'species' => 'Feline',
            'breed' => 'Siamese',
            'gender' => 'spayed_female',
            'age' => 2,
            'current_weight_kg' => '3.80',
            'allergies' => 'Cephalexin',
        ]);

        $createdPatient = Patient::query()->where('name_or_tag', 'Luna')->firstOrFail();
        $storeResponse->assertRedirect(route('portal.patients.show', $createdPatient->id));
        $this->assertEquals(['Cephalexin'], $createdPatient->allergies);

        // Update patient demographics
        $updateResponse = $this->put("/portal/patients/{$createdPatient->id}", [
            'client_id' => $this->client->id,
            'name_or_tag' => 'Luna Updated',
            'species' => 'Feline',
            'gender' => 'spayed_female',
            'status' => 'active',
            'current_weight_kg' => '4.10',
            'allergies' => 'Cephalexin, Doxycycline',
        ]);

        $updateResponse->assertRedirect(route('portal.patients.show', $createdPatient->id));
        $this->assertDatabaseHas('patients', [
            'id' => $createdPatient->id,
            'name_or_tag' => 'Luna Updated',
            'current_weight_kg' => '4.10',
        ]);
    }

    public function test_level_2_case_detail_shows_compact_visit_cards_and_no_media(): void
    {
        $this->withSession(['active_tenant_id' => $this->alphaTenant->id])
            ->actingAs($this->doctor, 'web');

        $response = $this->get("/portal/cases/{$this->case->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cases/Show')
            ->has('case')
            ->where('case.case_number', $this->case->case_number)
            ->has('visits', 1)
            ->where('visits.0.title', 'Day 1 Physical Exam & Vitals')
            ->missing('mediaImages')
        );
    }

    public function test_case_creation_and_update(): void
    {
        $this->withSession(['active_tenant_id' => $this->alphaTenant->id])
            ->actingAs($this->doctor, 'web');

        // Create new case under patient
        $complaint = 'Right forelimb lameness after jumping - '.bin2hex(random_bytes(4));
        $storeResponse = $this->post('/portal/cases', [
            'patient_id' => $this->patient->id,
            'chief_complaint' => $complaint,
            'priority' => 'routine',
            'case_type' => 'outpatient',
            'primary_veterinarian_id' => $this->doctor->id,
            'provisional_diagnosis' => 'Carpal Sprain',
        ]);

        $newCase = ClinicalCase::query()->where('chief_complaint', $complaint)->firstOrFail();
        $storeResponse->assertRedirect(route('portal.cases.show', $newCase->id));

        // Update case
        $updateResponse = $this->put("/portal/cases/{$newCase->id}", [
            'chief_complaint' => 'Right forelimb lameness confirmed improved.',
            'priority' => 'routine',
            'case_type' => 'outpatient',
            'status' => 'discharged',
            'final_diagnosis' => 'Grade 1 Carpal Ligamentous Sprain',
        ]);

        $updateResponse->assertRedirect(route('portal.cases.show', $newCase->id));
        $this->assertDatabaseHas('cases', [
            'id' => $newCase->id,
            'final_diagnosis' => 'Grade 1 Carpal Ligamentous Sprain',
        ]);
    }

    public function test_level_3_visit_workspace_soap_dosing_media_and_signing(): void
    {
        $this->withSession(['active_tenant_id' => $this->alphaTenant->id])
            ->actingAs($this->doctor, 'web');

        // 1. Visit workspace loads
        $response = $this->get("/portal/visits/{$this->visit->id}");
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Visits/Show')
            ->has('visit')
            ->has('case')
            ->has('patient')
            ->has('products')
            ->has('administeredDoses')
            ->has('mediaImages')
        );

        // 2. Record SOAP Clinical Exam with vitals
        $soapResponse = $this->post("/portal/visits/{$this->visit->id}/soap", [
            'subjective' => 'Owner reports dog vomited twice yellow bile. Refusing kibble.',
            'objective' => 'Mild cranial abdominal discomfort. T: 38.6, HR: 115, RR: 24. MM pink.',
            'assessment' => 'Acute gastroenteritis, rule out foreign body obstruction.',
            'plan' => 'Administer Cerenia 1mg/kg SC, plain lactated Ringer fluid therapy.',
            'temperature_c' => 38.6,
            'heart_rate_bpm' => 115,
            'respiratory_rate' => 24,
            'weight_kg' => 32.7,
        ]);

        $soapResponse->assertSessionHas('success');
        $this->assertDatabaseHas('clinical_progress_notes', [
            'visit_id' => $this->visit->id,
            'heart_rate_bpm' => 115,
        ]);

        // 3. Administer medication dose and verify inventory deduction
        $sku = 'MED-CER-'.strtoupper(bin2hex(random_bytes(3)));
        $product = InventoryProduct::query()->create([
            'name' => 'Cerenia Injectable 10mg/ml',
            'sku' => $sku,
            'base_unit' => MeasurementBaseUnit::Ml,
            'is_controlled' => false,
            'controlled_schedule' => 'uncontrolled',
            'reorder_threshold' => '5.0000',
        ]);

        // Receive stock first
        $this->post('/portal/inventory/receive', [
            'product_id' => $product->id,
            'batch_number' => 'LOT-CER-99',
            'expiration_date' => now()->addYear()->format('Y-m-d'),
            'initial_base_quantity' => '50.0000',
            'cost_per_base_unit' => '2.5000',
        ]);

        $doseResponse = $this->post("/portal/visits/{$this->visit->id}/dose", [
            'product_id' => $product->id,
            'dose_amount_base_units' => 3.2000,
            'route_administered' => 'SC',
            'clinical_notes' => 'Administered interscapular',
        ]);

        $doseResponse->assertSessionHas('success');
        $this->assertDatabaseHas('dose_administrations', [
            'case_id' => $this->case->id,
            'product_id' => $product->id,
            'dose_amount_base_units' => '3.2000',
        ]);

        // Stock decreased from 50 to 46.8
        $this->assertEquals('46.8000', $product->calculateTotalStock());

        // 4. Attach diagnostic radiograph (MinIO S3)
        $file = UploadedFile::fake()->image('lateral_abdomen.jpg', 800, 600);
        $mediaResponse = $this->post("/portal/visits/{$this->visit->id}/media", [
            'image' => $file,
            'title' => 'Lateral Abdominal Radiograph',
        ]);

        $mediaResponse->assertSessionHas('success');
        $this->assertDatabaseHas('images', [
            'imageable_id' => $this->visit->id,
            'title' => 'Lateral Abdominal Radiograph',
        ]);

        // 5. Sign and lock visit record
        $signResponse = $this->post("/portal/visits/{$this->visit->id}/sign");
        $signResponse->assertSessionHas('success');

        $this->visit->refresh();
        $this->assertTrue($this->visit->is_signed);

        // 6. Attempting to modify SOAP on signed visit is rejected
        $lockedSoapResponse = $this->post("/portal/visits/{$this->visit->id}/soap", [
            'subjective' => 'Attempting post-lock mutation.',
        ]);

        $lockedSoapResponse->assertSessionHasErrors(['soap']);
    }

    public function test_pharmacy_inventory_and_stock_receipt(): void
    {
        $this->withSession(['active_tenant_id' => $this->alphaTenant->id])
            ->actingAs($this->doctor, 'web');

        // Create product
        $sku = 'MED-CLAV-'.strtoupper(bin2hex(random_bytes(3)));
        $this->post('/portal/inventory', [
            'name' => 'Clavamox Drops 15ml',
            'sku' => $sku,
            'base_unit' => 'ml',
            'controlled_schedule' => 'uncontrolled',
            'reorder_threshold' => '10.0000',
        ])->assertRedirect(route('portal.inventory.index'));

        $product = InventoryProduct::query()->where('sku', $sku)->firstOrFail();

        // Receive batch intake
        $this->post('/portal/inventory/receive', [
            'product_id' => $product->id,
            'batch_number' => 'LOT-CLAV-401',
            'expiration_date' => now()->addMonths(6)->format('Y-m-d'),
            'initial_base_quantity' => '100.0000',
        ])->assertRedirect(route('portal.inventory.index'));

        $this->assertEquals('100.0000', $product->calculateTotalStock());

        // Log waste
        $this->post('/portal/inventory/waste', [
            'product_id' => $product->id,
            'amount_base_units' => 2.5000,
            'reason' => 'Vial dropped and shattered on treatment floor.',
        ])->assertRedirect(route('portal.inventory.index'));

        $this->assertEquals('97.5000', $product->calculateTotalStock());
    }

    public function test_bilingual_locale_switch(): void
    {
        $this->withSession(['active_tenant_id' => $this->alphaTenant->id])
            ->actingAs($this->doctor, 'web');

        $response = $this->get('/portal/dashboard?lang=ar');

        $response->assertStatus(200);
        $this->assertEquals('ar', session('locale'));
        $response->assertHeader('Content-Language', 'ar');
        $response->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'ar')
            ->where('dir', 'rtl')
        );
    }
}
