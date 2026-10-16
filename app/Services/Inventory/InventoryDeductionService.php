<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\ContainerStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryContainer;
use App\Models\InventoryMovementLedger;
use App\Models\InventoryProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryDeductionService
{
    /**
     * Execute a high-precision fractional inventory deduction using FEFO and open-container priority.
     *
     * @return array{product: InventoryProduct, total_deducted: string, remaining_stock: string, movements: list<InventoryMovementLedger>}
     *
     * @throws ValidationException
     */
    public function deduct(
        InventoryProduct $product,
        string $requiredQuantity,
        User $actor,
        StockMovementType $movementType = StockMovementType::DoseAdministration,
        ?User $witness = null,
        ?string $reason = null,
        ?string $idempotencyKey = null
    ): array {
        // Enforce BCMath scale 4
        $requiredQuantity = number_format((float) $requiredQuantity, 4, '.', '');

        if (bccomp($requiredQuantity, '0.0000', 4) <= 0) {
            throw ValidationException::withMessages([
                'quantity' => ['Deduction quantity must be greater than zero.'],
            ]);
        }

        // Regulatory Compliance: Enforce witness for controlled pharmaceuticals
        if ($product->is_controlled) {
            if (! $witness instanceof User) {
                throw ValidationException::withMessages([
                    'witness' => ['Controlled substances (DEA Schedule II-V) strictly require dual-clinician witness verification.'],
                ]);
            }

            if (! $witness->role->canWitnessControlledSubstances()) {
                throw ValidationException::withMessages([
                    'witness' => ["Staff member with role '{$witness->role->value}' is not authorized to witness controlled substances."],
                ]);
            }
        }

        return DB::connection('tenant')->transaction(function () use (
            $product,
            $requiredQuantity,
            $actor,
            $movementType,
            $witness,
            $reason,
            $idempotencyKey
        ): array {
            // Lock product row to prevent race conditions
            $lockedProduct = InventoryProduct::query()
                ->where('id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $totalAvailable = $lockedProduct->calculateTotalStock();

            if (bccomp($totalAvailable, $requiredQuantity, 4) < 0) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        "Insufficient stock for '{$lockedProduct->name}'. Available: {$totalAvailable} {$lockedProduct->base_unit->value}, Requested: {$requiredQuantity} {$lockedProduct->base_unit->value}.",
                    ],
                ]);
            }

            $remainingToDeduct = $requiredQuantity;
            $movements = [];

            // ─── Step 1: Open Container Priority (FEFO) ───
            $openContainers = InventoryContainer::query()
                ->join('inventory_batches', 'inventory_containers.batch_id', '=', 'inventory_batches.id')
                ->where('inventory_containers.product_id', $lockedProduct->id)
                ->where('inventory_containers.status', ContainerStatus::Open)
                ->where('inventory_containers.current_base_balance', '>', 0)
                ->orderBy('inventory_batches.expiration_date', 'asc')
                ->select('inventory_containers.*')
                ->lockForUpdate()
                ->get();

            foreach ($openContainers as $container) {
                if (bccomp($remainingToDeduct, '0.0000', 4) <= 0) {
                    break;
                }

                $deductFromThis = bccomp((string) $container->current_base_balance, $remainingToDeduct, 4) >= 0
                    ? $remainingToDeduct
                    : (string) $container->current_base_balance;

                $newBalance = bcsub((string) $container->current_base_balance, $deductFromThis, 4);
                $remainingToDeduct = bcsub($remainingToDeduct, $deductFromThis, 4);

                $container->current_base_balance = $newBalance;
                if (bccomp($newBalance, '0.0000', 4) === 0) {
                    $container->status = ContainerStatus::Depleted;
                    $container->depleted_at = now();
                }
                $container->save();

                $movements[] = InventoryMovementLedger::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'product_id' => $lockedProduct->id,
                    'batch_id' => $container->batch_id,
                    'container_id' => $container->id,
                    'movement_type' => $movementType,
                    'quantity' => '-'.$deductFromThis,
                    'balance_after' => $newBalance,
                    'reason' => $reason,
                    'witnessed_by_user_id' => $witness?->id,
                    'recorded_by_user_id' => $actor->id,
                    'created_at' => now(),
                ]);
            }

            // ─── Step 2: Unsealed / Sealed Containers (FEFO) ───
            if (bccomp($remainingToDeduct, '0.0000', 4) > 0) {
                $sealedContainers = InventoryContainer::query()
                    ->join('inventory_batches', 'inventory_containers.batch_id', '=', 'inventory_batches.id')
                    ->where('inventory_containers.product_id', $lockedProduct->id)
                    ->where('inventory_containers.status', ContainerStatus::Sealed)
                    ->orderBy('inventory_batches.expiration_date', 'asc')
                    ->select('inventory_containers.*')
                    ->lockForUpdate()
                    ->get();

                foreach ($sealedContainers as $container) {
                    if (bccomp($remainingToDeduct, '0.0000', 4) <= 0) {
                        break;
                    }

                    // Break the seal and open container
                    $container->status = ContainerStatus::Open;
                    $container->opened_at = now();
                    $container->opened_by_user_id = $actor->id;

                    $deductFromThis = bccomp((string) $container->current_base_balance, $remainingToDeduct, 4) >= 0
                        ? $remainingToDeduct
                        : (string) $container->current_base_balance;

                    $newBalance = bcsub((string) $container->current_base_balance, $deductFromThis, 4);
                    $remainingToDeduct = bcsub($remainingToDeduct, $deductFromThis, 4);

                    $container->current_base_balance = $newBalance;
                    if (bccomp($newBalance, '0.0000', 4) === 0) {
                        $container->status = ContainerStatus::Depleted;
                        $container->depleted_at = now();
                    }
                    $container->save();

                    $movements[] = InventoryMovementLedger::query()->create([
                        'idempotency_key' => $idempotencyKey,
                        'product_id' => $lockedProduct->id,
                        'batch_id' => $container->batch_id,
                        'container_id' => $container->id,
                        'movement_type' => $movementType,
                        'quantity' => '-'.$deductFromThis,
                        'balance_after' => $newBalance,
                        'reason' => $reason,
                        'witnessed_by_user_id' => $witness?->id,
                        'recorded_by_user_id' => $actor->id,
                        'created_at' => now(),
                    ]);
                }
            }

            $remainingStock = $lockedProduct->calculateTotalStock();

            return [
                'product' => $lockedProduct,
                'total_deducted' => $requiredQuantity,
                'remaining_stock' => $remainingStock,
                'movements' => $movements,
            ];
        });
    }
}
