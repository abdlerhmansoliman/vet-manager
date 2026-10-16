<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InventoryProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryProduct
 */
class InventoryProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'base_unit' => $this->base_unit->value,
            'is_controlled' => $this->is_controlled,
            'controlled_schedule' => $this->controlled_schedule->value,
            'controlled_schedule_label' => $this->controlled_schedule->label(),
            'reorder_threshold' => (string) $this->reorder_threshold,
            'package_description' => $this->package_description,
            'packaging_factor' => (string) $this->packaging_factor,
            'total_stock' => $this->calculateTotalStock(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
