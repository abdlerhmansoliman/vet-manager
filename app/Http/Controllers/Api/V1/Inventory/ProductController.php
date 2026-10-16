<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Actions\Inventory\CreateProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CreateProductRequest;
use App\Models\InventoryProduct;
use App\Transformers\InventoryProductTransformer;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = InventoryProduct::query()->orderBy('name')->get();

        return response()->json([
            'data' => InventoryProductTransformer::items($products),
        ]);
    }

    public function store(CreateProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $product = $action->execute($request->toDTO());

        return response()->json([
            'data' => InventoryProductTransformer::item($product),
        ], 201);
    }
}
