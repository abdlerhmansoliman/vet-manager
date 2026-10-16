<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidTenantToken
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Ensure the authenticated token is explicitly scoped to the resolved tenant.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantManager->current();
        $user = $request->user();

        if ($user !== null && $tenant instanceof Tenant) {
            if (! $user->tokenCan("tenant:{$tenant->id}")) {
                return new JsonResponse([
                    'message' => 'Token is not authorized for the requested clinic.',
                ], Response::HTTP_UNAUTHORIZED);
            }
        }

        return $next($request);
    }
}
