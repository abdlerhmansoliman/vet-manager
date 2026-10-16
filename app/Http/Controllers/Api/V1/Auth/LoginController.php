<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    /**
     * Authenticate staff against the active tenant and issue a scoped token.
     */
    public function __invoke(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $result = $action->execute($request->toDTO());

        return response()->json([
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
            'tenant' => [
                'id' => $result['tenant']->id,
                'name' => $result['tenant']->name,
                'slug' => $result['tenant']->slug,
            ],
            'capabilities' => $result['user']->role->capabilities(),
        ]);
    }
}
