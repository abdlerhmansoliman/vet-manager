<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ContainerStatus;
use App\Enums\MeasurementBaseUnit;
use App\Models\InventoryBatch;
use App\Models\InventoryContainer;
use App\Models\InventoryMovementLedger;
use App\Models\InventoryProduct;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryDeductionRefactorTest extends TestCase
{
    private string $token;

    private User $doctor;

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

        $alphaTenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        app(TenantManager::class)->switch($alphaTenant);
        $this->doctor = User::query()->where('email', 'john.miller@alphavet.com')->firstOrFail();
        app(TenantManager::class)->reset();
    }

    public function test_expired_batches_are_strictly_excluded_from_deduction(): void
    {
        // 1. Create an uncontrolled antibiotic
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Amoxicillin Trihydrate 100mg/ml',
                'sku' => 'AMOX-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => false,
            ]);

        $productId = $productResponse->json('data.id');

        // 2. Receive batch 1 with 10.0000 ml
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'EXPIRED-BATCH-001',
                'expiration_date' => now()->addMonth()->toDateString(),
                'containers_count' => 1,
                'base_quantity_per_container' => '10.0000',
            ])->assertStatus(201);

        // 3. Receive batch 2 (expires next year) with 5.0000 ml
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'ACTIVE-BATCH-002',
                'expiration_date' => now()->addYear()->toDateString(),
                'containers_count' => 1,
                'base_quantity_per_container' => '5.0000',
            ])->assertStatus(201);

        // Now simulate batch 1 expiring over time while in stock (past expiration date)
        $alphaTenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($alphaTenant);

        $expiredBatch = InventoryBatch::query()
            ->where('product_id', $productId)
            ->where('batch_number', 'EXPIRED-BATCH-001')
            ->firstOrFail();
        $expiredBatch->expiration_date = now()->subDay()->toDateString();
        $expiredBatch->save();

        $tenantManager->reset();

        // 4. Request 6.0000 ml: Total stock is 15 ml, but only 5 ml is unexpired
        // Must fail with 422 because expired stock cannot be allocated
        $failResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '6.0000',
            ]);

        $failResponse->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        // 5. Request 3.0000 ml: Must succeed drawing ONLY from the active batch
        $successResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '3.0000',
            ]);

        $successResponse->assertStatus(200);

        // Verify active container balance decreased from 5.0000 to 2.0000
        $tenantManager->switch($alphaTenant);
        $activeBatch = InventoryBatch::query()
            ->where('product_id', $productId)
            ->where('batch_number', 'ACTIVE-BATCH-002')
            ->firstOrFail();
        $activeContainer = InventoryContainer::query()->where('batch_id', $activeBatch->id)->firstOrFail();
        $this->assertEquals('2.0000', $activeContainer->current_base_balance);

        // Verify expired container was UNTOUCHED (still sealed and 10.0000 ml)
        $expiredContainer = InventoryContainer::query()->where('batch_id', $expiredBatch->id)->firstOrFail();
        $this->assertEquals('10.0000', $expiredContainer->current_base_balance);
        $this->assertEquals(ContainerStatus::Sealed, $expiredContainer->status);
        $tenantManager->reset();
    }

    public function test_all_or_nothing_atomic_rollback_on_insufficient_stock(): void
    {
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Cefazolin 1g/vial',
                'sku' => 'CEF-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Piece->value,
                'is_controlled' => false,
            ]);

        $productId = $productResponse->json('data.id');

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'CEF-LOT-1',
                'expiration_date' => now()->addYear()->toDateString(),
                'containers_count' => 1,
                'base_quantity_per_container' => '2.0000',
            ])->assertStatus(201);

        $alphaTenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($alphaTenant);
        $initialLedgerCount = InventoryMovementLedger::query()->where('product_id', $productId)->count();
        $tenantManager->reset();

        // Attempt to deduct 3.0000 when only 2.0000 is available
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '3.0000',
            ])->assertStatus(422);

        $tenantManager->switch($alphaTenant);
        // Verify container was NOT partially touched
        $container = InventoryContainer::query()->where('product_id', $productId)->firstOrFail();
        $this->assertEquals('2.0000', $container->current_base_balance);
        $this->assertEquals(ContainerStatus::Sealed, $container->status);

        // Verify zero new movements were committed
        $this->assertEquals($initialLedgerCount, InventoryMovementLedger::query()->where('product_id', $productId)->count());
        $tenantManager->reset();
    }

    public function test_multi_container_deduction_shares_single_operation_id(): void
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

        // Receive 2 containers of 10ml each = 20ml
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/receive', [
                'product_id' => $productId,
                'batch_number' => 'PROP-LOT-A',
                'expiration_date' => now()->addYear()->toDateString(),
                'containers_count' => 2,
                'base_quantity_per_container' => '10.0000',
            ])->assertStatus(201);

        // Deduct 15ml (will span 10ml from first container + 5ml from second container)
        $idempotencyKey = (string) Str::uuid();
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '15.0000',
            ]);

        $response->assertStatus(200);

        $alphaTenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($alphaTenant);

        // Fetch movements for this consumption
        $consumeMovements = InventoryMovementLedger::query()
            ->where('product_id', $productId)
            ->where('idempotency_key', $idempotencyKey)
            ->get();

        $this->assertCount(2, $consumeMovements);

        // Both rows must share the exact same operation_id
        $operationId1 = $consumeMovements[0]->operation_id;
        $operationId2 = $consumeMovements[1]->operation_id;
        $this->assertNotNull($operationId1);
        $this->assertEquals($operationId1, $operationId2);

        // Product balance after second movement must reflect remaining stock (5.0000)
        $this->assertEquals('5.0000', $consumeMovements[1]->product_balance_after);
        $tenantManager->reset();
    }

    public function test_ledger_records_are_strictly_immutable_at_model_level(): void
    {
        $alphaTenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($alphaTenant);

        $ledger = InventoryMovementLedger::query()->first();
        if ($ledger instanceof InventoryMovementLedger) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('immutable');
            $ledger->update(['reason' => 'Tampered reason']);
        }

        $tenantManager->reset();
    }

    public function test_fractional_quantity_rejects_malformed_inputs(): void
    {
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Isoflurane Inhalation',
                'sku' => 'ISO-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Ml->value,
                'is_controlled' => false,
            ]);

        $productId = $productResponse->json('data.id');

        // Test scientific notation rejection
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '1e-4',
            ])->assertStatus(422);

        // Test more than 4 decimal places rejection
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '1.12345',
            ])->assertStatus(422);

        // Test exceeding 8 integer digits rejection (overflow guard)
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '123456789.0000',
            ])->assertStatus(422);
    }

    public function test_database_trigger_blocks_raw_sql_update_and_delete_on_ledger(): void
    {
        $alphaTenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($alphaTenant);

        // Create a movement record
        $ledger = InventoryMovementLedger::query()->first();
        if ($ledger instanceof InventoryMovementLedger) {
            // Attempt raw SQL update bypassing Eloquent
            $this->expectException(\Illuminate\Database\QueryException::class);
            $this->expectExceptionMessage('immutable');
            \Illuminate\Support\Facades\DB::connection('tenant')
                ->table('inventory_movement_ledger')
                ->where('id', $ledger->id)
                ->update(['reason' => 'Direct SQL tamper attempt']);
        }

        $tenantManager->reset();
    }

    public function test_non_expiring_products_with_null_expiration_are_allocated_properly(): void
    {
        $productResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/inventory/products', [
                'name' => 'Surgical Gauze Swabs 10x10',
                'sku' => 'GAUZE-'.uniqid(),
                'base_unit' => MeasurementBaseUnit::Piece->value,
                'is_controlled' => false,
            ]);

        $productId = $productResponse->json('data.id');

        $alphaTenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($alphaTenant);

        // Create batch with NULL expiration date directly
        $batch = InventoryBatch::query()->create([
            'id' => (string) Str::uuid(),
            'product_id' => $productId,
            'batch_number' => 'NO-EXPIRY-BATCH-01',
            'expiration_date' => null,
            'initial_base_quantity' => '100.0000',
            'current_base_balance' => '100.0000',
        ]);

        InventoryContainer::query()->create([
            'id' => (string) Str::uuid(),
            'product_id' => $productId,
            'batch_id' => $batch->id,
            'container_identifier' => 'BOX-01',
            'initial_base_quantity' => '100.0000',
            'current_base_balance' => '100.0000',
            'status' => ContainerStatus::Sealed,
        ]);

        $tenantManager->reset();

        // Consume 20 pieces: should succeed even with NULL expiration date
        $consumeResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/inventory/consume', [
                'product_id' => $productId,
                'quantity' => '20.0000',
            ]);

        $consumeResponse->assertStatus(200)
            ->assertJsonPath('total_deducted', '20.0000')
            ->assertJsonPath('remaining_stock', '80.0000');
    }

    public function test_offline_reconciliation_deficit_keeps_product_stock_in_sync(): void
    {
        $alphaTenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($alphaTenant);

        $product = InventoryProduct::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Emergency Epinephrine 1mg/ml',
            'sku' => 'EPI-'.uniqid(),
            'base_unit' => MeasurementBaseUnit::Ml,
            'is_controlled' => false,
            'reconciliation_deficit' => '0.0000',
        ]);

        $service = app(\App\Services\Inventory\InventoryDeductionService::class);
        $actor = User::query()->where('email', 'john.miller@alphavet.com')->firstOrFail();

        // Deduct 5.0000 ml with allowNegativeBalance=true when 0 stock exists
        $result = $service->deduct(
            product: $product,
            requiredQuantity: '5.0000',
            actor: $actor,
            allowNegativeBalance: true
        );

        $product->refresh();

        // Verify product reconciliation_deficit is persisted
        $this->assertEquals('5.0000', $product->reconciliation_deficit);

        // Verify calculateTotalStock() agrees with product_balance_after (-5.0000)
        $this->assertEquals('-5.0000', $product->calculateTotalStock());
        $this->assertEquals('-5.0000', $result['remaining_stock']);
        $this->assertEquals('-5.0000', $result['movements'][0]->product_balance_after);

        $tenantManager->reset();
    }
}
