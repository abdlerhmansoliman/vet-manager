<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $identifier = $this->resolveIdentifier($request);

        if ($identifier === null || trim($identifier) === '') {
            return new JsonResponse([
                'message' => 'Tenant identification required. Please provide X-Tenant-ID header.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $tenant = $this->tenantManager->resolve($identifier);

        if (! $tenant instanceof Tenant) {
            return new JsonResponse([
                'message' => 'Tenant not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($tenant->isSuspended() || ! $tenant->isActive()) {
            return new JsonResponse([
                'message' => 'Tenant account suspended or inactive.',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->tenantManager->switch($tenant);
        $request->attributes->set('tenant', $tenant);

        $response = $next($request);

        // Append tenant context header to responses for client debugging & routing validation
        $response->headers->set('X-Tenant-Resolved', $tenant->id);

        return $response;
    }

    /**
     * Resolve the tenant identifier from headers or subdomain.
     */
    private function resolveIdentifier(Request $request): ?string
    {
        $header = $request->header('X-Tenant-ID');
        if (! empty($header)) {
            return (string) $header;
        }

        $host = $request->getHost();
        $parts = explode('.', $host);

        // e.g. "alphavet.localhost" or "alphavet.vetmanager.io"
        if (count($parts) >= 2 && $parts[0] !== 'www' && $parts[0] !== 'localhost' && $parts[0] !== 'api') {
            return $parts[0];
        }

        return null;
    }
}
