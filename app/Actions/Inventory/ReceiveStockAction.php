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

            // Default base_quantity_per_container to packaging_factor if omitted
            $baseQuantityPerContainer = $dto->baseQuantityPerContainer;
            if ($baseQuantityPerContainer === null || bccomp((string) $baseQuantityPerContainer, '0.0000', 4) <= 0) {
                $baseQuantityPerContainer = (string) $product->packaging_factor;
            }
            if (bccomp((string) $baseQuantityPerContainer, '0.0000', 4) <= 0) {
                $baseQuantityPerContainer = '1.0000';
            }

            // Calculate cost_per_base_unit from package_buy_price if provided
            $costPerBaseUnit = $dto->costPerBaseUnit;
            if ($costPerBaseUnit === null && $dto->packageBuyPrice !== null) {
                $factor = bccomp((string) $product->packaging_factor, '0.0000', 4) > 0
                    ? (string) $product->packaging_factor
                    : $baseQuantityPerContainer;

                $costPerBaseUnit = bcdiv($dto->packageBuyPrice, $factor, 4);
            }

            $totalReceived = bcmul((string) $dto->containersCount, $baseQuantityPerContainer, 4);

            $batch = InventoryBatch::query()->create([
                'product_id' => $product->id,
                'batch_number' => $dto->batchNumber,
                'expiration_date' => $dto->expirationDate,
                'cost_per_base_unit' => $costPerBaseUnit,
                'initial_base_quantity' => $totalReceived,
                'current_base_balance' => $totalReceived,
            ]);

            for ($i = 1; $i <= $dto->containersCount; $i++) {
                $container = InventoryContainer::query()->create([
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'container_identifier' => "{$dto->batchNumber} #{$i}",
                    'initial_base_quantity' => $baseQuantityPerContainer,
                    'current_base_balance' => $baseQuantityPerContainer,
                    'status' => ContainerStatus::Sealed,
                ]);

                InventoryMovementLedger::query()->create([
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'container_id' => $container->id,
                    'movement_type' => StockMovementType::Receive,
                    'quantity' => $baseQuantityPerContainer,
                    'balance_after' => $baseQuantityPerContainer,
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
