<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Get the authenticated staff member with their clinical profile and tenant capabilities.
     */
    public function __invoke(Request $request, TenantManager $tenantManager): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load('profile');

        /** @var Tenant $tenant */
        $tenant = $tenantManager->current();

        return response()->json([
            'user' => new UserResource($user),
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'capabilities' => $user->role->capabilities(),
        ]);
    }
}
