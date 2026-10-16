<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Actions\Inventory\WasteStockAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\WasteStockRequest;
use App\Models\User;
use App\Transformers\InventoryMovementTransformer;
use Illuminate\Http\JsonResponse;

class InventoryWasteController extends Controller
{
    public function __invoke(WasteStockRequest $request, WasteStockAction $action): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $idempotencyKey = $request->header('Idempotency-Key');

        $result = $action->execute($request->toDTO(), $actor, $idempotencyKey);

        return response()->json([
            'message' => 'Pharmaceutical wastage discarded and recorded in compliance ledger.',
            'product_id' => $result['product']->id,
            'total_wasted' => $result['total_deducted'],
            'remaining_stock' => $result['remaining_stock'],
            'movements' => InventoryMovementTransformer::items($result['movements']),
        ]);
    }
}
