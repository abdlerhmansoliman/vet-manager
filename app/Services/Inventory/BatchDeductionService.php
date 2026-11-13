<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\InventoryBatch;
use App\Models\InventoryMovementLedger;
use App\Models\InventoryProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
     *     batches_affected: list<InventoryBatch>
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
        // Enforce BCMath scale 4
        $doseAmountBaseUnits = number_format((float) $doseAmountBaseUnits, 4, '.', '');

        if (bccomp($doseAmountBaseUnits, '0.0000', 4) <= 0) {
            throw ValidationException::withMessages([
                'dose_amount_base_units' => ['Dose quantity must be strictly greater than zero.'],
            ]);
        }

        return DB::connection('tenant')->transaction(function () use (
            $product,
            $doseAmountBaseUnits,
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

            // Retrieve active batches ordered by FEFO (earliest expiration date first)
            $batches = InventoryBatch::query()
                ->where('product_id', $lockedProduct->id)
                ->where('current_base_balance', '>', 0)
                ->orderBy('expiration_date', 'asc')
                ->lockForUpdate()
                ->get();

            // Calculate total stock available across all active batches
            $totalAvailable = '0.0000';
            foreach ($batches as $batch) {
                $totalAvailable = bcadd($totalAvailable, (string) $batch->current_base_balance, 4);
            }

            if (bccomp($totalAvailable, $doseAmountBaseUnits, 4) < 0 && ! $allowNegativeBalance) {
                throw ValidationException::withMessages([
                    'dose_amount_base_units' => [
                        "Insufficient stock for '{$lockedProduct->name}'. Available: {$totalAvailable} {$lockedProduct->base_unit->value}, Requested: {$doseAmountBaseUnits} {$lockedProduct->base_unit->value}.",
                    ],
                ]);
            }

            $remainingToDeduct = $doseAmountBaseUnits;
            $movements = [];
            $batchesAffected = [];

            foreach ($batches as $batch) {
                if (bccomp($remainingToDeduct, '0.0000', 4) <= 0) {
                    break;
                }

                $batchBalance = (string) $batch->current_base_balance;

                $deductFromThis = bccomp($batchBalance, $remainingToDeduct, 4) >= 0
                    ? $remainingToDeduct
                    : $batchBalance;

                $newBalance = bcsub($batchBalance, $deductFromThis, 4);
                $remainingToDeduct = bcsub($remainingToDeduct, $deductFromThis, 4);

                $batch->current_base_balance = $newBalance;
                $batch->save();

                $batchesAffected[] = $batch;

                // Log immutable ledger movement tied to the batch (container_id is null)
                $movements[] = InventoryMovementLedger::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'product_id' => $lockedProduct->id,
                    'batch_id' => $batch->id,
                    'container_id' => null,
                    'movement_type' => $movementType,
                    'quantity' => '-'.$deductFromThis,
                    'balance_after' => $newBalance,
                    'reason' => $reason ?? "Clinical dose deduction of {$deductFromThis} {$lockedProduct->base_unit->value}",
                    'recorded_by_user_id' => $actor->id,
                    'created_at' => now(),
                ]);
            }

            if ($allowNegativeBalance && bccomp($remainingToDeduct, '0.0000', 4) > 0) {
                $targetBatch = $batches->last() ?? InventoryBatch::query()->where('product_id', $lockedProduct->id)->latest('expiration_date')->first();
                if (! $targetBatch instanceof InventoryBatch) {
                    $targetBatch = InventoryBatch::query()->create([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'product_id' => $lockedProduct->id,
                        'batch_number' => 'BATCH-RECONCILIATION-001',
                        'expiration_date' => now()->addYear(),
                        'initial_base_quantity' => '0.0000',
                        'current_base_balance' => '0.0000',
                    ]);
                }

                $batchBalance = (string) $targetBatch->current_base_balance;
                $newBalance = bcsub($batchBalance, $remainingToDeduct, 4);
                $targetBatch->current_base_balance = $newBalance;
                $targetBatch->save();

                if (! in_array($targetBatch, $batchesAffected, true)) {
                    $batchesAffected[] = $targetBatch;
                }

                $movements[] = InventoryMovementLedger::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'product_id' => $lockedProduct->id,
                    'batch_id' => $targetBatch->id,
                    'container_id' => null,
                    'movement_type' => $movementType,
                    'quantity' => '-'.$remainingToDeduct,
                    'balance_after' => $newBalance,
                    'reason' => ($reason ?? "Clinical dose deduction of {$remainingToDeduct} {$lockedProduct->base_unit->value}").' (Offline reconciliation deficit)',
                    'recorded_by_user_id' => $actor->id,
                    'created_at' => now(),
                ]);
            }

            $remainingStock = $lockedProduct->calculateTotalStock();

            return [
                'product' => $lockedProduct,
                'total_deducted' => $doseAmountBaseUnits,
                'remaining_stock' => $remainingStock,
                'movements' => $movements,
                'batches_affected' => $batchesAffected,
            ];
        });
    }
}
