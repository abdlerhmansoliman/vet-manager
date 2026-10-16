<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\InventoryMovementLedger;

class InventoryMovementTransformer extends Transformer
{
    /**
     * @param  InventoryMovementLedger  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'id' => $item->id,
            'container_id' => $item->container_id,
            'movement_type' => $item->movement_type->value,
            'quantity' => (string) $item->quantity,
            'balance_after' => (string) $item->balance_after,
            'reason' => $item->reason,
            'recorded_by_user_id' => $item->recorded_by_user_id,
            'witnessed_by_user_id' => $item->witnessed_by_user_id,
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }
}
