<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Actions\Inventory\CreateProductAction;
use App\Actions\Inventory\DeleteProductAction;
use App\Actions\Inventory\UpdateProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CreateProductRequest;
use App\Http\Requests\Inventory\UpdateProductRequest;
use App\Models\InventoryProduct;
use App\Transformers\InventoryProductTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = InventoryProduct::query()->orderBy('name');

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->get();

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

    public function show(InventoryProduct $product): JsonResponse
    {
        return response()->json([
            'data' => InventoryProductTransformer::item($product),
        ]);
    }

    public function update(InventoryProduct $product, UpdateProductRequest $request, UpdateProductAction $action): JsonResponse
    {
        $updated = $action->execute($product, $request->toDTO());

        return response()->json([
            'data' => InventoryProductTransformer::item($updated),
        ]);
    }

    public function destroy(InventoryProduct $product, DeleteProductAction $action): JsonResponse
    {
        $action->execute($product);

        return response()->json([
            'message' => 'Product archived successfully.',
        ]);
    }
}
