<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\InventoryProduct;
use Illuminate\Validation\ValidationException;

class DeleteProductAction
{
    /**
     * Soft-delete a product, blocking deletion if active physical stock exists.
     *
     * @throws ValidationException
     */
    public function execute(InventoryProduct $product): void
    {
        $totalStock = $product->calculateTotalStock();

        if (bccomp($totalStock, '0.0000', 4) > 0) {
            throw ValidationException::withMessages([
                'product' => ["Cannot archive product '{$product->name}' with active physical stock balance ({$totalStock} {$product->base_unit->value}). Deplete or adjust stock to 0.0000 first."],
            ]);
        }

        $product->delete();
    }
}
