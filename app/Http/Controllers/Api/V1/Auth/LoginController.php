<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Transformers\UserTransformer;
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
            'user' => UserTransformer::item($result['user']),
            'tenant' => [
                'id' => $result['tenant']->id,
                'name' => $result['tenant']->name,
                'slug' => $result['tenant']->slug,
            ],
            'capabilities' => $result['user']->role->capabilities(),
        ]);
    }
}
