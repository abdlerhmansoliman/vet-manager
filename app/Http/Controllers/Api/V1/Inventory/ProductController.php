<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Actions\Inventory\CreateProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CreateProductRequest;
use App\Http\Resources\InventoryProductResource;
use App\Models\InventoryProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $products = InventoryProduct::query()->orderBy('name')->get();

        return InventoryProductResource::collection($products);
    }

    public function store(CreateProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $product = $action->execute($request->toDTO());

        return (new InventoryProductResource($product))
            ->response()
            ->setStatusCode(201);
    }
}
