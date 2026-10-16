<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InventoryBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryBatch
 */
class InventoryBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'expiration_date' => $this->expiration_date->format('Y-m-d'),
            'cost_per_base_unit' => $this->cost_per_base_unit !== null ? (string) $this->cost_per_base_unit : null,
        ];
    }
}
