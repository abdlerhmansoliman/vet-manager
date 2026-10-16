<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\InventoryContainer;

class InventoryContainerTransformer extends Transformer
{
    /**
     * @param  InventoryContainer  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'batch_id' => $item->batch_id,
            'container_identifier' => $item->container_identifier,
            'initial_base_quantity' => (string) $item->initial_base_quantity,
            'current_base_balance' => (string) $item->current_base_balance,
            'status' => $item->status->value,
            'opened_at' => $item->opened_at?->toIso8601String(),
            'depleted_at' => $item->depleted_at?->toIso8601String(),
        ];
    }
}
