<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InventoryContainer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryContainer
 */
class InventoryContainerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'container_identifier' => $this->container_identifier,
            'initial_base_quantity' => (string) $this->initial_base_quantity,
            'current_base_balance' => (string) $this->current_base_balance,
            'status' => $this->status->value,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'depleted_at' => $this->depleted_at?->toIso8601String(),
        ];
    }
}
