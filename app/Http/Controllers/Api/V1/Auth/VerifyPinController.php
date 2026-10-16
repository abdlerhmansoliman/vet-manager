<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\VerifyPinAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyPinRequest;
use App\Transformers\WitnessVerificationTransformer;
use Illuminate\Http\JsonResponse;

class VerifyPinController extends Controller
{
    /**
     * Verify secondary clinician PIN for controlled substance witnessing.
     */
    public function __invoke(VerifyPinRequest $request, VerifyPinAction $action): JsonResponse
    {
        $witness = $action->execute($request->toDTO());

        return response()->json([
            'data' => WitnessVerificationTransformer::item($witness),
        ]);
    }
}
