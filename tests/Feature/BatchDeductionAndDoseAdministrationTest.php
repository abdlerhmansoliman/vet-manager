<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MeasurementBaseUnit;
use App\Models\Client;
use App\Models\ClinicalCase;
use App\Models\InventoryBatch;
use App\Models\InventoryMovementLedger;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Tests\TestCase;

class BatchDeductionAndDoseAdministrationTest extends TestCase
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
        $this->tenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $this->veterinarian = User::query()->where('email', 'john.miller@alphavet.com')->firstOrFail();

        $client = Client::factory()->create();
        $this->patient = Patient::factory()->create([
            'client_id' => $client->id,
            'name_or_tag' => 'Barnaby',
        ]);

        $this->case = ClinicalCase::query()->create([
            'case_number' => 'CAS-EMAR-'.uniqid(),
            'patient_id' => $this->patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
            'case_type' => 'outpatient',
            'status' => 'open',
            'priority' => 'routine',
            'chief_complaint' => 'Post-op pain management',
            'admitted_at' => now(),
        ]);

        $tenantManager->reset();
    }

    /**
     * Test Task 3: Receiving UX with package_buy_price and default base_quantity_per_container.
     * Scenario: 100 bottles of medication, each containing 100 grams, with whole box buy price = $50.00.
     */
    public function test_receive_stock_with_package_buy_price_and_default_quantity(): void
    {
        // 1. Create product with packaging_factor = 100.0000 grams
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Amoxicillin Powder 100g',
                'sku' => 'AMX-100G-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Gram->value,
                'package_description' => '100g Bottle',
                'packaging_factor' => '100.0000',
            ]);

        $productResponse->assertStatus(201);
        $productId = $productResponse->json('data.id');

        // 2. Receive 100 bottles with package_buy_price = 50.00 (omitting base_quantity_per_container)
        $receiveResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'AMX-LOT-01',
                'expiration_date' => '2028-12-31',
                'package_buy_price' => '50.00',
                'containers_count' => 100,
            ]);

        $receiveResponse->assertStatus(201)
            ->assertJsonPath('containers_count', 100)
            ->assertJsonPath('total_received', '10000.0000')
            ->assertJsonPath('total_stock', '10000.0000');

        // Verify batch recorded cost_per_base_unit = 50.00 / 100 = 0.5000
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $batch = InventoryBatch::query()->where('product_id', $productId)->firstOrFail();
        $this->assertEquals('0.5000', $batch->cost_per_base_unit);
        $this->assertEquals('10000.0000', $batch->initial_base_quantity);
        $this->assertEquals('10000.0000', $batch->current_base_balance);

        $tenantManager->reset();
    }

    /**
     * Test Task 1 & 2: Administering a 5-gram dose directly from batch via FEFO (bypassing containers).
     */
    public function test_dose_administration_deducts_directly_from_batch_via_fefo(): void
    {
        // 1. Create product & receive 100 bottles x 100g = 10,000g
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Metronidazole Powder',
                'sku' => 'MTZ-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Gram->value,
                'packaging_factor' => '100.0000',
            ]);

        $productId = $productResponse->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'MTZ-BATCH-99',
                'expiration_date' => '2028-06-30',
                'containers_count' => 100,
                'base_quantity_per_container' => '100.0000',
            ])->assertStatus(201);

        // 2. Veterinarian administers a 5-gram dose to the patient encounter
        $doseResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/cases/{$this->case->id}/dose-administrations", [
                'product_id' => $productId,
                'dose_amount_base_units' => '5.0000',
                'route_administered' => 'PO',
                'clinical_notes' => 'Administered with meal. Dog swallowed without issue.',
            ]);

        $doseResponse->assertStatus(201)
            ->assertJsonPath('data.case_id', $this->case->id)
            ->assertJsonPath('data.patient_id', $this->patient->id)
            ->assertJsonPath('data.product_id', $productId)
            ->assertJsonPath('data.product_name', 'Metronidazole Powder')
            ->assertJsonPath('data.base_unit', 'g')
            ->assertJsonPath('data.dose_amount_base_units', '5.0000')
            ->assertJsonPath('data.route_administered', 'PO')
            ->assertJsonPath('data.clinical_notes', 'Administered with meal. Dog swallowed without issue.')
            ->assertJsonPath('inventory.total_deducted', '5.0000')
            ->assertJsonPath('inventory.remaining_stock', '9995.0000');

        // 3. Inspect database state
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        // Verify batch balance decreased from 10,000 to 9,995
        $batch = InventoryBatch::query()->where('product_id', $productId)->firstOrFail();
        $this->assertEquals('9995.0000', $batch->current_base_balance);

        // Verify movement ledger entry has batch_id and container_id is NULL
        $ledger = InventoryMovementLedger::query()
            ->where('product_id', $productId)
            ->where('movement_type', 'dose_administration')
            ->firstOrFail();

        $this->assertEquals($batch->id, $ledger->batch_id);
        $this->assertNull($ledger->container_id);
        $this->assertEquals('-5.0000', $ledger->quantity);
        $this->assertEquals('9995.0000', $ledger->balance_after);

        $tenantManager->reset();
    }

    /**
     * Test Task 1: Cascading FEFO deduction across multiple batches.
     * Batch A has 3g (expires first), Batch B has 10g (expires later).
     * Administering 5g should deplete Batch A completely and take 2g from Batch B.
     */
    public function test_cascading_fefo_deduction_across_multiple_batches(): void
    {
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Furosemide Solution',
                'sku' => 'FUR-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
            ]);

        $productId = $productResponse->json('data.id');

        // Batch A: Expiring sooner (2027-01-01), 3.0000 ml
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'BATCH-A-EARLY',
                'expiration_date' => '2027-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '3.0000',
            ])->assertStatus(201);

        // Batch B: Expiring later (2027-12-31), 10.0000 ml
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'BATCH-B-LATER',
                'expiration_date' => '2027-12-31',
                'containers_count' => 1,
                'base_quantity_per_container' => '10.0000',
            ])->assertStatus(201);

        // Administer 5.0000 ml: Requires 3.0000 ml from Batch A (depleted) + 2.0000 ml from Batch B (8.0000 ml left)
        $doseResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/cases/{$this->case->id}/dose-administrations", [
                'product_id' => $productId,
                'dose_amount_base_units' => '5.0000',
                'route_administered' => 'IV',
            ]);

        $doseResponse->assertStatus(201)
            ->assertJsonPath('inventory.total_deducted', '5.0000')
            ->assertJsonPath('inventory.remaining_stock', '8.0000')
            ->assertJsonPath('inventory.batches_depleted', 2);

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $batchA = InventoryBatch::query()->where('batch_number', 'BATCH-A-EARLY')->firstOrFail();
        $batchB = InventoryBatch::query()->where('batch_number', 'BATCH-B-LATER')->firstOrFail();

        $this->assertEquals('0.0000', $batchA->current_base_balance);
        $this->assertEquals('8.0000', $batchB->current_base_balance);

        $tenantManager->reset();
    }

    /**
     * Test deduction failure when stock is insufficient.
     */
    public function test_dose_administration_fails_when_insufficient_batch_stock(): void
    {
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Cefazolin Powder',
                'sku' => 'CFZ-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Gram->value,
            ]);

        $productId = $productResponse->json('data.id');

        // Receive only 2.0000 g
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'CFZ-BATCH-1',
                'expiration_date' => '2028-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '2.0000',
            ])->assertStatus(201);

        // Attempt to administer 5.0000 g
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/cases/{$this->case->id}/dose-administrations", [
                'product_id' => $productId,
                'dose_amount_base_units' => '5.0000',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['dose_amount_base_units']);
    }

    /**
     * Test Task 2: GET /api/v1/cases/{case}/dose-administrations returns patient medication administration history.
     */
    public function test_get_case_dose_administrations_returns_patient_medication_history(): void
    {
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Buprenorphine 0.3mg/ml',
                'sku' => 'BUP-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
            ]);

        $productId = $productResponse->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'BUP-BATCH-1',
                'expiration_date' => '2027-10-10',
                'containers_count' => 1,
                'base_quantity_per_container' => '10.0000',
            ])->assertStatus(201);

        // Administer 2 separate doses at different times
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/cases/{$this->case->id}/dose-administrations", [
                'product_id' => $productId,
                'dose_amount_base_units' => '0.3000',
                'route_administered' => 'SC',
                'clinical_notes' => 'Initial pain dose',
            ])->assertStatus(201);

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/cases/{$this->case->id}/dose-administrations", [
                'product_id' => $productId,
                'dose_amount_base_units' => '0.2000',
                'route_administered' => 'SC',
                'clinical_notes' => 'Follow-up dose',
            ])->assertStatus(201);

        // Fetch medication administration history
        $historyResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/cases/{$this->case->id}/dose-administrations");

        $historyResponse->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.route_administered', 'SC')
            ->assertJsonPath('data.0.product_name', 'Buprenorphine 0.3mg/ml');
    }

    /**
     * Test Searchable Medication & Formulary Endpoint: ?search= query parameter filtering.
     */
    public function test_products_search_endpoint_filters_by_name_and_sku(): void
    {
        $unique = uniqid();
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => "Tramadol {$unique}",
                'sku' => "TRM-{$unique}",
                'base_unit' => MeasurementBaseUnit::Tablet->value,
            ])->assertStatus(201);

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => "Gabapentin {$unique}",
                'sku' => "GBP-{$unique}",
                'base_unit' => MeasurementBaseUnit::Tablet->value,
            ])->assertStatus(201);

        // Search for "Tramadol"
        $searchResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/inventory/products?search=Tramadol%20{$unique}");

        $searchResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', "Tramadol {$unique}");
    }
}
