<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Actions\Inventory\ReceiveStockAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ReceiveStockRequest;
use App\Http\Resources\InventoryBatchResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class InventoryReceiveController extends Controller
{
    public function __invoke(ReceiveStockRequest $request, ReceiveStockAction $action): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $result = $action->execute($request->toDTO(), $actor);

        return response()->json([
            'message' => 'Inventory stock received successfully.',
            'batch' => new InventoryBatchResource($result['batch']),
            'containers_count' => $result['containers_count'],
            'total_received' => $result['total_received'],
            'total_stock' => $result['total_stock'],
        ], 201);
    }
}
