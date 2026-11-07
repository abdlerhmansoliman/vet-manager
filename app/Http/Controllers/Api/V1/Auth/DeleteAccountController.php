<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\DeleteAccountAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DeleteAccountRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DeleteAccountController extends Controller
{
    /**
     * Self-service account deletion (App Store Guideline 5.1.1(v)).
     */
    public function __invoke(DeleteAccountRequest $request, DeleteAccountAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->execute($user, (string) $request->validated('password'));

        return response()->json([
            'message' => 'Your account has been deleted successfully.',
        ]);
    }
}
