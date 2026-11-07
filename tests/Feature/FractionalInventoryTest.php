<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ControlledSubstanceSchedule;
use App\Enums\MeasurementBaseUnit;
use App\Enums\StaffRole;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Illuminate\Support\Str;
use Tests\TestCase;

class FractionalInventoryTest extends TestCase
{
    private string $token;

    private string $witnessId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TenantSeeder::class);
        $this->seed(TenantStaffSeeder::class);

        // Authenticate as Dr. John Miller (Owner) on AlphaPet
        $loginResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/login', [
                'email' => 'john.miller@alphavet.com',
                'password' => 'password123',
            ]);

        $this->token = $loginResponse->json('token');

        // Fetch Dr. Sarah Connor's ID for witness testing
        $alphaTenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        app(TenantManager::class)->switch($alphaTenant);
        $sarah = User::query()->where('email', 'sarah.connor@vetservices.com')->firstOrFail();
        $this->witnessId = $sarah->id;
        app(TenantManager::class)->reset();
    }

    public function test_product_creation_and_stock_receipt(): void
    {
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Diazepam Injectable 5mg/ml',
                'sku' => 'DIAZ-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => true,
                'controlled_schedule' => ControlledSubstanceSchedule::ScheduleIV->value,
                'package_description' => '10ml multidose amber vial',
                'packaging_factor' => '10.0000',
            ]);

        $productResponse->assertStatus(201)
            ->assertJsonPath('data.base_unit', 'ml')
            ->assertJsonPath('data.is_controlled', true)
            ->assertJsonPath('data.total_stock', '0.0000');

        $productId = $productResponse->json('data.id');

        // Receive 2 vials x 10.0000 ml = 20.0000 ml
        $receiveResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LOT-'.rand(1000, 9999),
                'expiration_date' => '2028-12-31',
                'cost_per_base_unit' => '1.5000',
                'containers_count' => 2,
                'base_quantity_per_container' => '10.0000',
            ]);

        $receiveResponse->assertStatus(201)
            ->assertJsonPath('containers_count', 2)
            ->assertJsonPath('total_received', '20.0000')
            ->assertJsonPath('total_stock', '20.0000');

        // Verify stock breakdown inspection
        $stockResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/inventory/products/{$productId}/stock");

        $stockResponse->assertStatus(200)
            ->assertJsonPath('data.total_base_stock', '20.0000')
            ->assertJsonCount(0, 'data.open_containers')
            ->assertJsonCount(2, 'data.sealed_containers');
    }

    public function test_fractional_fefo_deduction_and_open_container_priority(): void
    {
        // Create Ketamine product
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Ketamine 100mg/ml',
                'sku' => 'KET-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => true,
                'controlled_schedule' => ControlledSubstanceSchedule::ScheduleIII->value,
            ]);

        $productId = $productResponse->json('data.id');

        // Receive 2 vials x 50.0000 ml = 100.0000 ml
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'KET-LOT-01',
                'expiration_date' => '2027-06-30',
                'containers_count' => 2,
                'base_quantity_per_container' => '50.0000',
            ])->assertStatus(201);

        // Deduct 0.7500 ml with witness Dr. Sarah Connor (PIN 5678)
        $idempotencyKey1 = (string) Str::uuid();
        $consumeResponse1 = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', $idempotencyKey1)
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '0.7500',
                'witness_id' => $this->witnessId,
                'witness_pin' => '5678',
                'reason' => 'Canine Pre-medication Sedation',
            ]);

        $consumeResponse1->assertStatus(200)
            ->assertJsonPath('total_deducted', '0.7500')
            ->assertJsonPath('remaining_stock', '99.2500');

        // Check container state: 1 open (balance: 49.2500), 1 sealed (balance: 50.0000)
        $stockResponse1 = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/inventory/products/{$productId}/stock");

        $stockResponse1->assertStatus(200)
            ->assertJsonCount(1, 'data.open_containers')
            ->assertJsonCount(1, 'data.sealed_containers')
            ->assertJsonPath('data.open_containers.0.current_base_balance', '49.2500');

        // Deduct another 1.2500 ml: should consume from the OPEN container first (Open container priority)
        $idempotencyKey2 = (string) Str::uuid();
        $consumeResponse2 = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', $idempotencyKey2)
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '1.2500',
                'witness_id' => $this->witnessId,
                'witness_pin' => '5678',
                'reason' => 'Feline General Anesthesia',
            ]);

        $consumeResponse2->assertStatus(200)
            ->assertJsonPath('total_deducted', '1.2500')
            ->assertJsonPath('remaining_stock', '98.0000');

        // Open container balance should now be exactly 48.0000 ml, sealed container remains untouched
        $stockResponse2 = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/inventory/products/{$productId}/stock");

        $stockResponse2->assertStatus(200)
            ->assertJsonPath('data.open_containers.0.current_base_balance', '48.0000')
            ->assertJsonPath('data.sealed_containers.0.current_base_balance', '50.0000');
    }

    public function test_fefo_expiration_priority_breaks_seal_on_earliest_expiring_batch_first(): void
    {
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Propofol 10mg/ml',
                'sku' => 'PROP-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => false,
            ]);

        $productId = $productResponse->json('data.id');

        // Receive Later Expiring Batch (2029-01-01)
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LATER-2029',
                'expiration_date' => '2029-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '20.0000',
            ])->assertStatus(201);

        // Receive Earlier Expiring Batch (2027-01-01)
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'SOONER-2027',
                'expiration_date' => '2027-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '20.0000',
            ])->assertStatus(201);

        // Deduct 5.0000 ml
        $consumeResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '5.0000',
            ]);

        $consumeResponse->assertStatus(200);

        // Verify that the container from the SOONER batch was opened and deducted
        $stockResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/inventory/products/{$productId}/stock");

        $stockResponse->assertStatus(200)
            ->assertJsonPath('data.open_containers.0.container_identifier', 'SOONER-2027 #1')
            ->assertJsonPath('data.open_containers.0.current_base_balance', '15.0000')
            ->assertJsonPath('data.sealed_containers.0.container_identifier', 'LATER-2029 #1')
            ->assertJsonPath('data.sealed_containers.0.current_base_balance', '20.0000');
    }

    public function test_controlled_substance_requires_authorized_witness_and_valid_pin(): void
    {
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Fentanyl Injectable 50mcg/ml',
                'sku' => 'FENT-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => true,
                'controlled_schedule' => ControlledSubstanceSchedule::ScheduleII->value,
            ]);

        $productId = $productResponse->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'FENT-LOT',
                'expiration_date' => '2028-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '10.0000',
            ])->assertStatus(201);

        // Attempting deduction without witness fails with 422
        $noWitnessResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '1.0000',
            ]);

        $noWitnessResponse->assertStatus(422)
            ->assertJsonValidationErrors(['witness']);

        // Attempting deduction with invalid witness PIN fails with 422
        $wrongPinResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '1.0000',
                'witness_id' => $this->witnessId,
                'witness_pin' => '0000',
            ]);

        $wrongPinResponse->assertStatus(422)
            ->assertJsonValidationErrors(['witness_pin']);
    }

    public function test_idempotency_key_replay_protection(): void
    {
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Amoxicillin 250mg Tablets',
                'sku' => 'AMOX-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Tablet->value,
                'is_controlled' => false,
            ]);

        $productId = $productResponse->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'AMOX-LOT',
                'expiration_date' => '2028-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '100.0000',
            ])->assertStatus(201);

        $idempotencyKey = (string) Str::uuid();

        // First attempt (initial execution)
        $response1 = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '5.0000',
            ]);

        $response1->assertStatus(200)
            ->assertJsonPath('total_deducted', '5.0000')
            ->assertJsonPath('remaining_stock', '95.0000');

        $this->assertFalse($response1->headers->has('Idempotent-Replay'));

        // Second attempt with the SAME idempotency key (mobile network retry replay)
        $response2 = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '5.0000',
            ]);

        $response2->assertStatus(200)
            ->assertHeader('Idempotent-Replay', 'true')
            ->assertJsonPath('remaining_stock', '95.0000');

        // Total stock in database must remain 95.0000 (ZERO double-deduction)
        $stockResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/inventory/products/{$productId}/stock");

        $stockResponse->assertStatus(200)
            ->assertJsonPath('data.total_base_stock', '95.0000');
    }

    public function test_pharmaceutical_waste_recording(): void
    {
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Butorphanol 10mg/ml',
                'sku' => 'BUT-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => true,
                'controlled_schedule' => ControlledSubstanceSchedule::ScheduleIV->value,
            ]);

        $productId = $productResponse->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'BUT-LOT',
                'expiration_date' => '2028-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '10.0000',
            ])->assertStatus(201);

        // Waste 0.2500 ml residual syringe draw
        $wasteResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/waste', [
                'product_id' => $productId,
                'quantity' => '0.2500',
                'reason' => 'Draw syringe excess wastage discarded into chemical neutralizer',
                'witness_id' => $this->witnessId,
                'witness_pin' => '5678',
            ]);

        $wasteResponse->assertStatus(200)
            ->assertJsonPath('total_wasted', '0.2500')
            ->assertJsonPath('remaining_stock', '9.7500')
            ->assertJsonPath('movements.0.movement_type', 'wastage_discard');
    }

    public function test_product_show_and_404_handling(): void
    {
        $createResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Meloxicam 1.5mg/ml Oral Suspension',
                'sku' => 'MELOX-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
            ]);

        $createResponse->assertStatus(201);
        $productId = $createResponse->json('data.id');

        // 1. Show existing product
        $showResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/inventory/products/{$productId}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $productId)
            ->assertJsonPath('data.name', 'Meloxicam 1.5mg/ml Oral Suspension')
            ->assertJsonPath('data.total_stock', '0.0000');

        // 2. 404 for non-existent product UUID
        $missingUuid = (string) Str::uuid();
        $missingResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/inventory/products/{$missingUuid}");

        $missingResponse->assertStatus(404);
    }

    public function test_product_update_validates_unique_sku_ignoring_self(): void
    {
        $skuA = 'SKU-A-'.uniqid();
        $skuB = 'SKU-B-'.uniqid();

        $prodA = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Product A',
                'sku' => $skuA,
                'base_unit' => MeasurementBaseUnit::Tablet->value,
            ])->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Product B',
                'sku' => $skuB,
                'base_unit' => MeasurementBaseUnit::Tablet->value,
            ])->assertStatus(201);

        // Update Product A keeping its own SKU -> Should succeed
        $updateSelfResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/inventory/products/{$prodA}", [
                'name' => 'Product A Renamed',
                'sku' => $skuA,
            ]);

        $updateSelfResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Product A Renamed');

        // Update Product A with Product B's SKU -> Must fail with 422
        $duplicateSkuResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/inventory/products/{$prodA}", [
                'name' => 'Product A Renamed',
                'sku' => $skuB,
            ]);

        $duplicateSkuResponse->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_product_update_prevents_changing_base_unit_when_stock_containers_exist(): void
    {
        $sku = 'IMMUT-'.uniqid();
        $productId = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Enrofloxacin Injectable',
                'sku' => $sku,
                'base_unit' => MeasurementBaseUnit::Ml->value,
            ])->json('data.id');

        // Receive stock (creating container in 'ml')
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LOT-ENRO-1',
                'expiration_date' => '2028-06-30',
                'containers_count' => 1,
                'base_quantity_per_container' => '100.0000',
            ])->assertStatus(201);

        // Attempting to change base_unit from 'ml' to 'tablet' must fail with 422
        $invalidMutation = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/inventory/products/{$productId}", [
                'name' => 'Enrofloxacin Tablets',
                'sku' => $sku,
                'base_unit' => MeasurementBaseUnit::Tablet->value,
            ]);

        $invalidMutation->assertStatus(422)
            ->assertJsonValidationErrors(['base_unit'])
            ->assertJsonFragment(['Cannot change the base unit of a product with existing stock transactions or containers.']);
    }

    public function test_product_deletion_is_blocked_when_active_stock_balance_exists(): void
    {
        $productId = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Isoflurane Inhalation Anesthetic',
                'sku' => 'ISO-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
            ])->json('data.id');

        // Receive 250 ml bottle
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LOT-ISO-01',
                'expiration_date' => '2029-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '250.0000',
            ])->assertStatus(201);

        // Attempting to delete product with positive balance must be rejected
        $deleteResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/inventory/products/{$productId}");

        $deleteResponse->assertStatus(422)
            ->assertJsonValidationErrors(['product']);
    }

    public function test_product_deletion_succeeds_when_stock_is_zero(): void
    {
        $productId = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Disposable Syringes 3ml',
                'sku' => 'SYR-3ML-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Piece->value,
            ])->json('data.id');

        // Delete product with zero stock
        $deleteResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/inventory/products/{$productId}");

        $deleteResponse->assertStatus(200)
            ->assertJsonPath('message', 'Product archived successfully.');

        // Verify product no longer appears in active catalog listing
        $listResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/inventory/products');

        $listResponse->assertStatus(200);
        $ids = collect($listResponse->json('data'))->pluck('id')->all();
        $this->assertNotContains($productId, $ids);
    }

    public function test_product_creation_rejects_invalid_enums_and_negative_thresholds(): void
    {
        // 1. Invalid base_unit
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Invalid Product',
                'sku' => 'INV-'.uniqid(),
                'base_unit' => 'liters',
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['base_unit']);

        // 2. Negative reorder_threshold
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Invalid Product',
                'sku' => 'INV-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'reorder_threshold' => -10,
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['reorder_threshold']);

        // 3. Zero packaging_factor
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Invalid Product',
                'sku' => 'INV-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'packaging_factor' => 0,
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['packaging_factor']);
    }

    public function test_receive_rejects_zero_negative_quantities_and_past_expiration(): void
    {
        $productId = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Cefazolin 1g Vial',
                'sku' => 'CEF-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Piece->value,
            ])->json('data.id');

        // Past expiration date
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LOT-EXPIRED',
                'expiration_date' => '2020-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '10.0000',
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['expiration_date']);

        // Zero containers count
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LOT-ZERO',
                'expiration_date' => '2029-01-01',
                'containers_count' => 0,
                'base_quantity_per_container' => '10.0000',
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['containers_count']);

        // Negative quantity per container
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LOT-NEG',
                'expiration_date' => '2029-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '-5.0000',
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['base_quantity_per_container']);
    }

    public function test_fractional_deduction_splits_across_open_and_sealed_containers_atomically(): void
    {
        $productId = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Buprenorphine 0.3mg/ml',
                'sku' => 'BUP-SPLIT-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => true,
                'controlled_schedule' => ControlledSubstanceSchedule::ScheduleIV->value,
            ])->json('data.id');

        // Receive Batch 1: 1 vial x 1.2500 ml (Expires 2027-01-01)
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LOT-V1',
                'expiration_date' => '2027-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '1.2500',
            ])->assertStatus(201);

        // Receive Batch 2: 1 vial x 10.0000 ml (Expires 2028-01-01)
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LOT-V2',
                'expiration_date' => '2028-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '10.0000',
            ])->assertStatus(201);

        // First consume 0.2500 ml to make Vial 1 'open' with remaining balance 1.0000 ml
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '0.2500',
                'witness_id' => $this->witnessId,
                'witness_pin' => '5678',
            ])->assertStatus(200);

        // Now, deduct 3.0000 ml.
        // It must take 1.0000 ml from Vial 1 (depleting it), and 2.0000 ml from Vial 2 (leaving 8.0000 ml).
        $splitConsume = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '3.0000',
                'witness_id' => $this->witnessId,
                'witness_pin' => '5678',
            ]);

        $splitConsume->assertStatus(200)
            ->assertJsonPath('total_deducted', '3.0000')
            ->assertJsonPath('remaining_stock', '8.0000')
            ->assertJsonCount(2, 'movements'); // Verified: Exactly 2 atomic ledger rows generated

        // Check stock inspection
        $stockResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/inventory/products/{$productId}/stock");

        $stockResponse->assertStatus(200)
            ->assertJsonPath('data.total_base_stock', '8.0000')
            ->assertJsonCount(1, 'data.open_containers')
            ->assertJsonPath('data.open_containers.0.current_base_balance', '8.0000');
    }

    public function test_fractional_deduction_handles_exact_boundary_depletion_to_zero(): void
    {
        $productId = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Xylazine 20mg/ml',
                'sku' => 'XYL-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => false,
            ])->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'XYL-LOT',
                'expiration_date' => '2028-12-31',
                'containers_count' => 1,
                'base_quantity_per_container' => '20.0000',
            ])->assertStatus(201);

        // Deduct exactly 20.0000 ml
        $consumeResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '20.0000',
            ]);

        $consumeResponse->assertStatus(200)
            ->assertJsonPath('total_deducted', '20.0000')
            ->assertJsonPath('remaining_stock', '0.0000');

        $stockResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/inventory/products/{$productId}/stock");

        $stockResponse->assertStatus(200)
            ->assertJsonPath('data.total_base_stock', '0.0000')
            ->assertJsonCount(0, 'data.open_containers')
            ->assertJsonCount(0, 'data.sealed_containers');

        // Subsequent deduction must fail with 422 insufficient stock
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '0.5000',
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_fractional_deduction_fails_when_exceeding_stock_by_smallest_fraction(): void
    {
        $productId = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Atropine Sulfate 0.5mg/ml',
                'sku' => 'ATRO-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => false,
            ])->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'ATRO-LOT',
                'expiration_date' => '2028-12-31',
                'containers_count' => 1,
                'base_quantity_per_container' => '10.0000',
            ])->assertStatus(201);

        // Attempting to deduct 10.0001 ml when only 10.0000 ml exists
        $overdrawResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '10.0001',
            ]);

        $overdrawResponse->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
        $this->assertStringContainsString('Available: 10.0000 ml, Requested: 10.0001 ml.', (string) $overdrawResponse->json('errors.quantity.0'));
    }

    public function test_fractional_deduction_rejects_zero_and_negative_quantities(): void
    {
        $productId = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Saline Solution 0.9%',
                'sku' => 'SAL-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
            ])->json('data.id');

        // Zero quantity
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '0.0000',
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        // Negative quantity
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '-5.0000',
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_unauthorized_staff_role_cannot_witness_controlled_substances(): void
    {
        $alphaTenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($alphaTenant);

        // Create a Receptionist staff member
        $receptionist = User::query()->create([
            'name' => 'Receptionist Bob',
            'email' => 'receptionist.'.uniqid().'@alphavet.com',
            'password' => 'password123',
            'role' => StaffRole::Receptionist,
            'pin' => '7777',
            'is_active' => true,
        ]);
        $receptionistId = $receptionist->id;
        $tenantManager->reset();

        $productId = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Fentanyl Transdermal Solution',
                'sku' => 'FENT-SOL-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => true,
                'controlled_schedule' => ControlledSubstanceSchedule::ScheduleII->value,
            ])->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LOT-FENT',
                'expiration_date' => '2028-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '10.0000',
            ])->assertStatus(201);

        // Receptionist attempts to act as witness for Schedule II drug -> Must be rejected with 422
        $unauthorizedWitnessResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '1.0000',
                'witness_id' => $receptionistId,
                'witness_pin' => '7777',
            ]);

        $unauthorizedWitnessResponse->assertStatus(422)
            ->assertJsonValidationErrors(['witness'])
            ->assertJsonFragment(["Staff member with role 'receptionist' is not authorized to witness controlled substances."]);
    }

    public function test_controlled_substance_requires_witness_pin_if_witness_provided(): void
    {
        $productId = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Midazolam 5mg/ml',
                'sku' => 'MID-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => true,
                'controlled_schedule' => ControlledSubstanceSchedule::ScheduleIV->value,
            ])->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LOT-MID',
                'expiration_date' => '2028-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '10.0000',
            ])->assertStatus(201);

        // Witness ID provided without witness PIN -> Must be rejected with 422
        $missingPinResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '1.0000',
                'witness_id' => $this->witnessId,
            ]);

        $missingPinResponse->assertStatus(422)
            ->assertJsonValidationErrors(['witness_pin']);
    }

    public function test_uncontrolled_pharmaceutical_deduction_does_not_require_witness(): void
    {
        $productId = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Prednisolone 5mg Tablets',
                'sku' => 'PRED-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Tablet->value,
                'is_controlled' => false,
            ])->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LOT-PRED',
                'expiration_date' => '2028-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '100.0000',
            ])->assertStatus(201);

        // Deduct without any witness
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '4.0000',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('total_deducted', '4.0000')
            ->assertJsonPath('remaining_stock', '96.0000')
            ->assertJsonPath('movements.0.witnessed_by', null);
    }

    public function test_waste_recording_enforces_witness_on_controlled_substances(): void
    {
        $productId = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Methadone 10mg/ml',
                'sku' => 'METH-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => true,
                'controlled_schedule' => ControlledSubstanceSchedule::ScheduleII->value,
            ])->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LOT-METH',
                'expiration_date' => '2028-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '10.0000',
            ])->assertStatus(201);

        // Attempting wastage without witness fails with 422
        $noWitnessResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/waste', [
                'product_id' => $productId,
                'quantity' => '0.5000',
                'reason' => 'Dropped and broken draw syringe',
            ]);

        $noWitnessResponse->assertStatus(422)
            ->assertJsonValidationErrors(['witness']);
    }

    public function test_waste_recording_fails_when_quantity_exceeds_available_stock(): void
    {
        $productId = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Eye Wash Solution',
                'sku' => 'EYE-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => false,
            ])->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'LOT-EYE',
                'expiration_date' => '2028-01-01',
                'containers_count' => 1,
                'base_quantity_per_container' => '5.0000',
            ])->assertStatus(201);

        // Attempt to waste 6.0000 ml when only 5.0000 ml exists
        $overwasteResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/waste', [
                'product_id' => $productId,
                'quantity' => '6.0000',
                'reason' => 'Entire bottle contaminated',
            ]);

        $overwasteResponse->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }
}
