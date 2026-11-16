<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\ContainerStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryContainer;
use App\Models\InventoryMovementLedger;
use App\Models\InventoryProduct;
use App\Models\User;
use App\ValueObjects\FractionalQuantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryDeductionService
{
    /**
     * Execute a high-precision fractional inventory deduction using FEFO and open-container priority.
     *
     * @return array{
     *     product: InventoryProduct,
     *     total_deducted: string,
     *     remaining_stock: string,
     *     movements: list<InventoryMovementLedger>,
     *     operation_id: string,
     *     replayed: bool
     * }
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
        ?string $idempotencyKey = null,
        bool $allowNegativeBalance = false
    ): array {
        // Enforce strict scale-4 BCMath via FractionalQuantity Value Object
        $quantityVO = FractionalQuantity::fromString($requiredQuantity, 'quantity');
        $requiredQuantityStr = $quantityVO->value();

        // Regulatory Compliance: Enforce witness for controlled pharmaceuticals if defined
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
            $quantityVO,
            $requiredQuantityStr,
            $actor,
            $movementType,
            $witness,
            $reason,
            $idempotencyKey,
            $allowNegativeBalance
        ): array {
            // Lock product row to prevent concurrent race conditions
            $lockedProduct = InventoryProduct::query()
                ->where('id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Idempotency replay check
            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $existingMovements = InventoryMovementLedger::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->orderBy('created_at', 'asc')
                    ->get();

                if ($existingMovements->isNotEmpty()) {
                    $originalTotal = $existingMovements->reduce(
                        fn (string $carry, $m) => bcadd($carry, ltrim((string) $m->quantity, '-'), 4),
                        '0.0000'
                    );

                    if ($existingMovements->first()->product_id !== $lockedProduct->id || bccomp($originalTotal, $requiredQuantityStr, 4) !== 0) {
                        throw ValidationException::withMessages([
                            'idempotency_key' => ['Idempotency key was already used with a different product or quantity.'],
                        ]);
                    }

                    return [
                        'product' => $lockedProduct,
                        'total_deducted' => $originalTotal,
                        'remaining_stock' => $lockedProduct->calculateTotalStock(),
                        'movements' => $existingMovements->all(),
                        'operation_id' => $existingMovements->first()->operation_id ?? (string) Str::uuid(),
                        'replayed' => true,
                    ];
                }
            }

            $operationId = (string) Str::uuid();
            $now = now();
            $today = $now->toDateString();

            // 2. Fetch unexpired candidate containers (FEFO: earliest expiration first)
            // Query open containers (already unsealed)
            $openContainers = InventoryContainer::query()
                ->join('inventory_batches', 'inventory_containers.batch_id', '=', 'inventory_batches.id')
                ->where('inventory_containers.product_id', $lockedProduct->id)
                ->where('inventory_containers.status', ContainerStatus::Open)
                ->where('inventory_containers.current_base_balance', '>', 0)
                ->where(function ($query) use ($today) {
                    $query->whereNull('inventory_batches.expiration_date')
                        ->orWhere('inventory_batches.expiration_date', '>=', $today);
                })
                ->orderByRaw('inventory_batches.expiration_date IS NULL ASC, inventory_batches.expiration_date ASC')
                ->orderBy('inventory_containers.opened_at', 'asc')
                ->select('inventory_containers.*')
                ->lockForUpdate()
                ->get();

            // Query sealed containers (unopened)
            $sealedContainers = InventoryContainer::query()
                ->join('inventory_batches', 'inventory_containers.batch_id', '=', 'inventory_batches.id')
                ->where('inventory_containers.product_id', $lockedProduct->id)
                ->where('inventory_containers.status', ContainerStatus::Sealed)
                ->where('inventory_containers.current_base_balance', '>', 0)
                ->where(function ($query) use ($today) {
                    $query->whereNull('inventory_batches.expiration_date')
                        ->orWhere('inventory_batches.expiration_date', '>=', $today);
                })
                ->orderByRaw('inventory_batches.expiration_date IS NULL ASC, inventory_batches.expiration_date ASC')
                ->orderBy('inventory_containers.id', 'asc')
                ->select('inventory_containers.*')
                ->lockForUpdate()
                ->get();

            // Calculate total unexpired stock available across candidates
            $totalAvailableVO = FractionalQuantity::zero();
            foreach ($openContainers as $c) {
                $totalAvailableVO = $totalAvailableVO->add((string) $c->current_base_balance);
            }
            foreach ($sealedContainers as $c) {
                $totalAvailableVO = $totalAvailableVO->add((string) $c->current_base_balance);
            }

            if (! $allowNegativeBalance && $totalAvailableVO->comp($quantityVO) < 0) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        "Insufficient unexpired stock for '{$lockedProduct->name}'. Available: {$totalAvailableVO->value()} {$lockedProduct->base_unit->value}, Requested: {$requiredQuantityStr} {$lockedProduct->base_unit->value}.",
                    ],
                ]);
            }

            $remainingToDeductVO = $quantityVO;
            $movements = [];
            $initialProductStock = $lockedProduct->calculateTotalStock();
            $runningProductBalance = $initialProductStock;

            // Helper closure to deduct from a single container
            $deductFromContainer = function (InventoryContainer $container, bool $isSealed) use (
                &$remainingToDeductVO,
                &$movements,
                &$runningProductBalance,
                $lockedProduct,
                $movementType,
                $reason,
                $witness,
                $actor,
                $idempotencyKey,
                $operationId,
                $now
            ): void {
                if ($remainingToDeductVO->isZero()) {
                    return;
                }

                $containerBalanceVO = FractionalQuantity::fromRaw((string) $container->current_base_balance);

                // Unseal if opening a sealed container
                if ($isSealed) {
                    $container->status = ContainerStatus::Open;
                    $container->opened_at = $now;
                    $container->opened_by_user_id = $actor->id;
                }

                // Determine allocation quantity
                if ($containerBalanceVO->comp($remainingToDeductVO) >= 0) {
                    $deductFromThisVO = $remainingToDeductVO;
                    $remainingToDeductVO = FractionalQuantity::zero();
                } else {
                    $deductFromThisVO = $containerBalanceVO;
                    $remainingToDeductVO = $remainingToDeductVO->sub($containerBalanceVO);
                }

                $newBalanceVO = $containerBalanceVO->sub($deductFromThisVO);
                $container->current_base_balance = $newBalanceVO->value();

                if ($newBalanceVO->isZero()) {
                    $container->status = ContainerStatus::Depleted;
                    $container->depleted_at = $now;
                }
                $container->save();

                // Update batch current_base_balance if present
                $batch = $container->batch;
                if ($batch && $batch->current_base_balance !== null && bccomp((string) $batch->current_base_balance, '0.0000', 4) > 0) {
                    $batchNew = bcsub((string) $batch->current_base_balance, $deductFromThisVO->value(), 4);
                    $batch->current_base_balance = bccomp($batchNew, '0.0000', 4) < 0 ? '0.0000' : $batchNew;
                    $batch->save();
                }

                $runningProductBalance = bcsub($runningProductBalance, $deductFromThisVO->value(), 4);

                $movements[] = InventoryMovementLedger::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'operation_id' => $operationId,
                    'product_id' => $lockedProduct->id,
                    'batch_id' => $container->batch_id,
                    'container_id' => $container->id,
                    'movement_type' => $movementType,
                    'quantity' => '-'.$deductFromThisVO->value(),
                    'balance_after' => $newBalanceVO->value(),
                    'product_balance_after' => $runningProductBalance,
                    'reason' => $reason,
                    'witnessed_by_user_id' => $witness?->id,
                    'recorded_by_user_id' => $actor->id,
                    'created_at' => $now,
                ]);
            };

            // ─── Step 1: Deduct from open containers (FEFO) ───
            foreach ($openContainers as $container) {
                if ($remainingToDeductVO->isZero()) {
                    break;
                }
                $deductFromContainer($container, false);
            }

            // ─── Step 2: Deduct from sealed containers (FEFO) ───
            if ($remainingToDeductVO->isPositive()) {
                foreach ($sealedContainers as $container) {
                    if ($remainingToDeductVO->isZero()) {
                        break;
                    }
                    $deductFromContainer($container, true);
                }
            }

            // ─── Step 3: All-or-nothing guard ───
            if (! $allowNegativeBalance && $remainingToDeductVO->isPositive()) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        "Insufficient unexpired stock to satisfy full deduction for '{$lockedProduct->name}'. Remaining unallocated: {$remainingToDeductVO->value()} {$lockedProduct->base_unit->value}.",
                    ],
                ]);
            }

            // ─── Step 4: Offline Reconciliation Deficit Handling ───
            if ($allowNegativeBalance && $remainingToDeductVO->isPositive()) {
                $deficitToAdd = $remainingToDeductVO->value();
                $lockedProduct->reconciliation_deficit = bcadd((string) ($lockedProduct->reconciliation_deficit ?? '0.0000'), $deficitToAdd, 4);
                $lockedProduct->save();

                $runningProductBalance = bcsub($runningProductBalance, $deficitToAdd, 4);

                $movements[] = InventoryMovementLedger::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'operation_id' => $operationId,
                    'product_id' => $lockedProduct->id,
                    'batch_id' => null,
                    'container_id' => null,
                    'movement_type' => $movementType,
                    'quantity' => '-'.$deficitToAdd,
                    'balance_after' => null,
                    'product_balance_after' => $runningProductBalance,
                    'reason' => ($reason ?? 'Clinical dose deduction').' (Offline reconciliation deficit)',
                    'witnessed_by_user_id' => $witness?->id,
                    'recorded_by_user_id' => $actor->id,
                    'created_at' => $now,
                ]);
            }

            $remainingStock = $lockedProduct->calculateTotalStock();

            return [
                'product' => $lockedProduct,
                'total_deducted' => $requiredQuantityStr,
                'remaining_stock' => $remainingStock,
                'movements' => $movements,
                'operation_id' => $operationId,
                'replayed' => false,
            ];
        });
    }
}
