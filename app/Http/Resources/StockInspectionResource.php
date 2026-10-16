<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InventoryProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryProduct
 */
class StockInspectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product' => new InventoryProductResource($this->resource),
            'total_base_stock' => $this->calculateTotalStock(),
            'open_containers' => InventoryContainerResource::collection($this->openContainers()->orderBy('created_at')->get()),
            'sealed_containers' => InventoryContainerResource::collection($this->sealedContainers()->orderBy('created_at')->get()),
            'batches' => InventoryBatchResource::collection($this->batches()->orderBy('expiration_date')->get()),
        ];
    }
}
