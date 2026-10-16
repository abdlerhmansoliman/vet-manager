<?php

declare(strict_types=1);

namespace App\Transformers;

use App\Models\InventoryProduct;

class StockInspectionTransformer extends Transformer
{
    /**
     * @param  InventoryProduct  $item
     * @return array<string, mixed>
     */
    public function transform(mixed $item): array
    {
        return [
            'product' => InventoryProductTransformer::item($item),
            'total_base_stock' => $item->calculateTotalStock(),
            'open_containers' => InventoryContainerTransformer::items($item->openContainers()->orderBy('created_at')->get()),
            'sealed_containers' => InventoryContainerTransformer::items($item->sealedContainers()->orderBy('created_at')->get()),
            'batches' => InventoryBatchTransformer::items($item->batches()->orderBy('expiration_date')->get()),
        ];
    }
}
