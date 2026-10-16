<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockInspectionResource;
use App\Models\InventoryProduct;

class InventoryStockController extends Controller
{
    /**
     * Inspect product stock breakdown across open and sealed containers.
     */
    public function __invoke(InventoryProduct $product): StockInspectionResource
    {
        return new StockInspectionResource($product);
    }
}
