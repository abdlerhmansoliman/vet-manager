<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ControlledSubstanceSchedule;
use App\Enums\MeasurementBaseUnit;
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
}
