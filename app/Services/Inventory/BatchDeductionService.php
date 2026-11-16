<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\InventoryBatch;
use App\Models\InventoryMovementLedger;
use App\Models\InventoryProduct;
use App\Models\User;
use App\ValueObjects\FractionalQuantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BatchDeductionService
{
    /**
     * Deduct stock directly from inventory batches using FEFO (First-Expired, First-Out) with BCMath scale 4.
     * Bypasses individual physical containers for practical clinical dispensing.
     *
     * @return array{
     *     product: InventoryProduct,
     *     total_deducted: string,
     *     remaining_stock: string,
     *     movements: list<InventoryMovementLedger>,
     *     batches_affected: list<InventoryBatch>,
     *     operation_id: string,
     *     replayed: bool
     * }
     *
     * @throws ValidationException
     */
    public function deduct(
        InventoryProduct $product,
        string $doseAmountBaseUnits,
        User $actor,
        StockMovementType $movementType = StockMovementType::DoseAdministration,
        ?string $reason = null,
        ?string $idempotencyKey = null,
        bool $allowNegativeBalance = false
    ): array {
        // Enforce strict scale-4 BCMath via FractionalQuantity Value Object
        $quantityVO = FractionalQuantity::fromString($doseAmountBaseUnits, 'dose_amount_base_units');
        $doseAmountStr = $quantityVO->value();

        return DB::connection('tenant')->transaction(function () use (
            $product,
            $quantityVO,
            $doseAmountStr,
            $actor,
            $movementType,
            $reason,
            $idempotencyKey,
            $allowNegativeBalance
        ): array {
            // Lock the product row for update to prevent concurrent race conditions
            $lockedProduct = InventoryProduct::query()
                ->where('id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Idempotency replay check
            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $existingMovements = InventoryMovementLedger::query()
                    ->where('product_id', $lockedProduct->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->orderBy('created_at', 'asc')
                    ->get();

                if ($existingMovements->isNotEmpty()) {
                    return [
                        'product' => $lockedProduct,
                        'total_deducted' => $doseAmountStr,
                        'remaining_stock' => $lockedProduct->calculateTotalStock(),
                        'movements' => $existingMovements->all(),
                        'batches_affected' => [],
                        'operation_id' => $existingMovements->first()->operation_id ?? (string) Str::uuid(),
                        'replayed' => true,
                    ];
                }
            }

            $operationId = (string) Str::uuid();
            $now = now();
            $today = $now->toDateString();

            // 2. Retrieve active unexpired batches ordered by FEFO (earliest expiration date first)
            $batches = InventoryBatch::query()
                ->where('product_id', $lockedProduct->id)
                ->where('current_base_balance', '>', 0)
                ->where(function ($query) use ($today) {
                    $query->whereNull('expiration_date')
                        ->orWhere('expiration_date', '>=', $today);
                })
                ->orderByRaw('expiration_date IS NULL ASC, expiration_date ASC')
                ->lockForUpdate()
                ->get();

            // Calculate total unexpired stock available across active batches
            $totalAvailableVO = FractionalQuantity::zero();
            foreach ($batches as $batch) {
                $totalAvailableVO = $totalAvailableVO->add((string) $batch->current_base_balance);
            }

            if (! $allowNegativeBalance && $totalAvailableVO->comp($quantityVO) < 0) {
                throw ValidationException::withMessages([
                    'dose_amount_base_units' => [
                        "Insufficient stock for '{$lockedProduct->name}'. Available: {$totalAvailableVO->value()} {$lockedProduct->base_unit->value}, Requested: {$doseAmountStr} {$lockedProduct->base_unit->value}.",
                    ],
                ]);
            }

            $remainingToDeductVO = $quantityVO;
            $movements = [];
            $batchesAffected = [];
            $runningProductBalance = $lockedProduct->calculateTotalStock();

            foreach ($batches as $batch) {
                if ($remainingToDeductVO->isZero()) {
                    break;
                }

                $batchBalanceVO = FractionalQuantity::fromRaw((string) $batch->current_base_balance);

                if ($batchBalanceVO->comp($remainingToDeductVO) >= 0) {
                    $deductFromThisVO = $remainingToDeductVO;
                    $remainingToDeductVO = FractionalQuantity::zero();
                } else {
                    $deductFromThisVO = $batchBalanceVO;
                    $remainingToDeductVO = $remainingToDeductVO->sub($batchBalanceVO);
                }

                $newBalanceVO = $batchBalanceVO->sub($deductFromThisVO);
                $batch->current_base_balance = $newBalanceVO->value();
                $batch->save();

                $batchesAffected[] = $batch;
                $runningProductBalance = bcsub($runningProductBalance, $deductFromThisVO->value(), 4);

                // Log immutable ledger movement tied to the batch (container_id is null)
                $movements[] = InventoryMovementLedger::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'operation_id' => $operationId,
                    'product_id' => $lockedProduct->id,
                    'batch_id' => $batch->id,
                    'container_id' => null,
                    'movement_type' => $movementType,
                    'quantity' => '-'.$deductFromThisVO->value(),
                    'balance_after' => $newBalanceVO->value(),
                    'product_balance_after' => $runningProductBalance,
                    'reason' => $reason ?? "Clinical dose deduction of {$deductFromThisVO->value()} {$lockedProduct->base_unit->value}",
                    'recorded_by_user_id' => $actor->id,
                    'created_at' => $now,
                ]);
            }

            // All-or-nothing check
            if (! $allowNegativeBalance && $remainingToDeductVO->isPositive()) {
                throw ValidationException::withMessages([
                    'dose_amount_base_units' => [
                        "Insufficient unexpired batch stock to satisfy full deduction for '{$lockedProduct->name}'. Remaining: {$remainingToDeductVO->value()} {$lockedProduct->base_unit->value}.",
                    ],
                ]);
            }

            // Offline reconciliation deficit handling
            if ($allowNegativeBalance && $remainingToDeductVO->isPositive()) {
                $targetBatch = $batches->last() ?? InventoryBatch::query()->where('product_id', $lockedProduct->id)->latest('expiration_date')->lockForUpdate()->first();
                if (! $targetBatch instanceof InventoryBatch) {
                    $targetBatch = InventoryBatch::query()->create([
                        'id' => (string) Str::uuid(),
                        'product_id' => $lockedProduct->id,
                        'batch_number' => 'BATCH-RECONCILIATION-'.substr((string) Str::uuid(), 0, 8),
                        'expiration_date' => now()->addYear(),
                        'initial_base_quantity' => '0.0000',
                        'current_base_balance' => '0.0000',
                    ]);
                }

                $newBalance = bcsub((string) $targetBatch->current_base_balance, $remainingToDeductVO->value(), 4);
                $targetBatch->current_base_balance = $newBalance;
                $targetBatch->save();

                if (! in_array($targetBatch, $batchesAffected, true)) {
                    $batchesAffected[] = $targetBatch;
                }

                $runningProductBalance = bcsub($runningProductBalance, $remainingToDeductVO->value(), 4);

                $movements[] = InventoryMovementLedger::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'operation_id' => $operationId,
                    'product_id' => $lockedProduct->id,
                    'batch_id' => $targetBatch->id,
                    'container_id' => null,
                    'movement_type' => $movementType,
                    'quantity' => '-'.$remainingToDeductVO->value(),
                    'balance_after' => $newBalance,
                    'product_balance_after' => $runningProductBalance,
                    'reason' => ($reason ?? "Clinical dose deduction of {$remainingToDeductVO->value()} {$lockedProduct->base_unit->value}").' (Offline reconciliation deficit)',
                    'recorded_by_user_id' => $actor->id,
                    'created_at' => $now,
                ]);
            }

            $remainingStock = $lockedProduct->calculateTotalStock();

            return [
                'product' => $lockedProduct,
                'total_deducted' => $doseAmountStr,
                'remaining_stock' => $remainingStock,
                'movements' => $movements,
                'batches_affected' => $batchesAffected,
                'operation_id' => $operationId,
                'replayed' => false,
            ];
        });
    }
}
