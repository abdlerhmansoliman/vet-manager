<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\DTOs\Inventory\ConsumeStockDTO;
use App\Enums\StockMovementType;
use App\Models\InventoryMovementLedger;
use App\Models\InventoryProduct;
use App\Models\User;
use App\Services\Inventory\InventoryDeductionService;
use Illuminate\Validation\ValidationException;

class WasteStockAction
{
    public function __construct(
        private readonly InventoryDeductionService $deductionService
    ) {}

    /**
     * @return array{product: InventoryProduct, total_deducted: string, remaining_stock: string, movements: list<InventoryMovementLedger>}
     *
     * @throws ValidationException
     */
    public function execute(ConsumeStockDTO $dto, User $actor, ?string $idempotencyKey = null): array
    {
        $product = InventoryProduct::query()->findOrFail($dto->productId);
        $witness = null;

        if ($dto->witnessId !== null) {
            $witness = User::query()->where('id', $dto->witnessId)->where('is_active', true)->first();

            if (! $witness instanceof User) {
                throw ValidationException::withMessages([
                    'witness_id' => ['Specified witness user does not exist or is inactive.'],
                ]);
            }

            if ($dto->witnessPin !== null && ! $witness->verifyPin($dto->witnessPin)) {
                throw ValidationException::withMessages([
                    'witness_pin' => ['Invalid witness PIN.'],
                ]);
            }
        }

        return $this->deductionService->deduct(
            product: $product,
            requiredQuantity: $dto->quantity,
            actor: $actor,
            movementType: StockMovementType::WastageDiscard,
            witness: $witness,
            reason: $dto->reason,
            idempotencyKey: $idempotencyKey
        );
    }
}
