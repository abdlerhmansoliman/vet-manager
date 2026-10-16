<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\InventoryBatch;

class InventoryBatchTransformer extends Transformer
{
    /**
     * @param  InventoryBatch  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'batch_number' => $item->batch_number,
            'expiration_date' => $item->expiration_date->format('Y-m-d'),
            'cost_per_base_unit' => $item->cost_per_base_unit !== null ? (string) $item->cost_per_base_unit : null,
        ];
    }
}
