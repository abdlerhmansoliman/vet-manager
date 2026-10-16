<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\InventoryProduct;

class InventoryProductTransformer extends Transformer
{
    /**
     * @param  InventoryProduct  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'base_unit' => $item->base_unit->value,
            'is_controlled' => $item->is_controlled,
            'controlled_schedule' => $item->controlled_schedule->value,
            'controlled_schedule_label' => $item->controlled_schedule->label(),
            'reorder_threshold' => (string) $item->reorder_threshold,
            'package_description' => $item->package_description,
            'packaging_factor' => (string) $item->packaging_factor,
            'total_stock' => $item->calculateTotalStock(),
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }
}
