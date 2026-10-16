<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\DTOs\Inventory\CreateProductDTO;
use App\Models\InventoryProduct;

class CreateProductAction
{
    public function execute(CreateProductDTO $dto): InventoryProduct
    {
        return InventoryProduct::query()->create([
            'name' => $dto->name,
            'sku' => $dto->sku,
            'base_unit' => $dto->baseUnit,
            'is_controlled' => $dto->isControlled,
            'controlled_schedule' => $dto->controlledSchedule,
            'reorder_threshold' => $dto->reorderThreshold,
            'package_description' => $dto->packageDescription,
            'packaging_factor' => $dto->packagingFactor,
        ]);
    }
}
