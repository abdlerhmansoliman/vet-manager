<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InventoryMovementLedger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryMovementLedger
 */
class InventoryMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'container_id' => $this->container_id,
            'movement_type' => $this->movement_type->value,
            'quantity' => (string) $this->quantity,
            'balance_after' => (string) $this->balance_after,
            'reason' => $this->reason,
            'recorded_by_user_id' => $this->recorded_by_user_id,
            'witnessed_by_user_id' => $this->witnessed_by_user_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
