<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\DTOs\Inventory\UpdateProductDTO;
use App\Models\InventoryProduct;
use Illuminate\Validation\ValidationException;

class UpdateProductAction
{
    /**
     * Update an existing inventory product.
     *
     * @throws ValidationException
     */
    public function execute(InventoryProduct $product, UpdateProductDTO $dto): InventoryProduct
    {
        // Enforce business invariant: cannot mutate base_unit if containers or movements exist
        if ($dto->baseUnit !== null && $dto->baseUnit !== $product->base_unit) {
            $hasContainers = $product->containers()->exists();
            $hasMovements = $product->movements()->exists();

            if ($hasContainers || $hasMovements) {
                throw ValidationException::withMessages([
                    'base_unit' => ['Cannot change the base unit of a product with existing stock transactions or containers.'],
                ]);
            }
        }

        $product->update([
            'name' => $dto->name,
            'sku' => $dto->sku,
            'base_unit' => $dto->baseUnit ?? $product->base_unit,
            'is_controlled' => $dto->isControlled,
            'controlled_schedule' => $dto->controlledSchedule,
            'reorder_threshold' => $dto->reorderThreshold,
            'package_description' => $dto->packageDescription,
            'packaging_factor' => $dto->packagingFactor,
        ]);

        return $product->refresh();
    }
}
