<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Handle an incoming state-modifying request with Idempotency replay protection.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey === null || trim((string) $idempotencyKey) === '') {
            return new JsonResponse([
                'message' => 'Idempotency-Key header is required for this operation.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $tenant = $this->tenantManager->current();
        $tenantId = $tenant instanceof Tenant ? $tenant->id : 'global';
        $redisKey = "idempotency:{$tenantId}:{$idempotencyKey}";

        // Check existing cached lock / result in Redis
        $existing = Redis::get($redisKey);

        if ($existing !== null) {
            $payload = json_decode((string) $existing, true);

            if (is_array($payload) && isset($payload['status'])) {
                if ($payload['status'] === 'IN_PROGRESS') {
                    return new JsonResponse([
                        'message' => 'A request with this Idempotency-Key is currently being processed.',
                    ], Response::HTTP_CONFLICT);
                }

                if ($payload['status'] === 'COMPLETED') {
                    $response = new JsonResponse(
                        data: json_decode((string) $payload['content'], true),
                        status: (int) $payload['code']
                    );
                    $response->headers->set('Idempotent-Replay', 'true');

                    return $response;
                }
            }
        }

        // Attempt atomic Redis lock: NX with 120s TTL
        $acquired = Redis::set($redisKey, json_encode(['status' => 'IN_PROGRESS']), 'EX', 120, 'NX');

        if (! $acquired) {
            return new JsonResponse([
                'message' => 'A request with this Idempotency-Key is currently being processed.',
            ], Response::HTTP_CONFLICT);
        }

        $response = $next($request);

        // If request was successful, persist response in Redis for 24h replay
        if ($response->isSuccessful()) {
            Redis::setex(
                $redisKey,
                86400,
                json_encode([
                    'status' => 'COMPLETED',
                    'code' => $response->getStatusCode(),
                    'content' => $response->getContent(),
                ])
            );
        } else {
            // Remove in-progress lock on validation or client errors to allow corrected retries
            Redis::del($redisKey);
        }

        return $response;
    }
}
