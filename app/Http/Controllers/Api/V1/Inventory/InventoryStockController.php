<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryProduct;
use App\Transformers\StockInspectionTransformer;
use Illuminate\Http\JsonResponse;

class InventoryStockController extends Controller
{
    /**
     * Inspect product stock breakdown across open and sealed containers.
     */
    public function __invoke(InventoryProduct $product): JsonResponse
    {
        return response()->json([
            'data' => StockInspectionTransformer::item($product),
        ]);
    }
}
