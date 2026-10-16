<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\DTOs\Inventory\ReceiveStockDTO;
use App\Enums\ContainerStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryBatch;
use App\Models\InventoryContainer;
use App\Models\InventoryMovementLedger;
use App\Models\InventoryProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReceiveStockAction
{
    /**
     * Receive a batch of containers into pharmaceutical inventory.
     *
     * @return array{batch: InventoryBatch, containers_count: int, total_received: string, total_stock: string}
     */
    public function execute(ReceiveStockDTO $dto, User $actor): array
    {
        return DB::connection('tenant')->transaction(function () use ($dto, $actor): array {
            $product = InventoryProduct::query()->findOrFail($dto->productId);

            $batch = InventoryBatch::query()->create([
                'product_id' => $product->id,
                'batch_number' => $dto->batchNumber,
                'expiration_date' => $dto->expirationDate,
                'cost_per_base_unit' => $dto->costPerBaseUnit,
            ]);

            $totalReceived = '0.0000';

            for ($i = 1; $i <= $dto->containersCount; $i++) {
                $container = InventoryContainer::query()->create([
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'container_identifier' => "{$dto->batchNumber} #{$i}",
                    'initial_base_quantity' => $dto->baseQuantityPerContainer,
                    'current_base_balance' => $dto->baseQuantityPerContainer,
                    'status' => ContainerStatus::Sealed,
                ]);

                $totalReceived = bcadd($totalReceived, $dto->baseQuantityPerContainer, 4);

                InventoryMovementLedger::query()->create([
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'container_id' => $container->id,
                    'movement_type' => StockMovementType::Receive,
                    'quantity' => $dto->baseQuantityPerContainer,
                    'balance_after' => $dto->baseQuantityPerContainer,
                    'reason' => "Initial receipt of Batch {$dto->batchNumber} Container #{$i}",
                    'recorded_by_user_id' => $actor->id,
                    'created_at' => now(),
                ]);
            }

            return [
                'batch' => $batch,
                'containers_count' => $dto->containersCount,
                'total_received' => $totalReceived,
                'total_stock' => $product->calculateTotalStock(),
            ];
        });
    }
}
