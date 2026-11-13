<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sync;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSyncMutationsJob;
use App\Services\Tenancy\TenantManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SyncController extends Controller
{
    /**
     * Query remote server deltas for mobile offline-first synchronization using cursor-based chunking.
     */
    public function pull(Request $request): JsonResponse
    {
        $limit = max(1, (int) $request->query('limit', 500));
        $since = $request->query('since');

        $fetchDeltas = function (string $tableName) use ($since, $limit) {
            $query = DB::table($tableName)
                ->orderBy('updated_at', 'asc')
                ->orderBy('id', 'asc')
                ->limit($limit);

            if ($since !== null && $since !== '') {
                $query->where('updated_at', '>=', $since);
            }

            return $query->get();
        };

        $clients = $fetchDeltas('clients');
        $patients = $fetchDeltas('patients');
        $species = DB::getSchemaBuilder()->hasTable('species') ? $fetchDeltas('species') : collect();
        $cases = $fetchDeltas('cases');
        $visits = DB::getSchemaBuilder()->hasTable('visits') ? $fetchDeltas('visits') : collect();
        $notesRaw = $fetchDeltas('clinical_progress_notes');
        $products = $fetchDeltas('inventory_products');
        $batches = $fetchDeltas('inventory_batches');
        $containers = $fetchDeltas('inventory_containers');

        $hasMore = $clients->count() >= $limit
            || $patients->count() >= $limit
            || $species->count() >= $limit
            || $cases->count() >= $limit
            || $visits->count() >= $limit
            || $notesRaw->count() >= $limit
            || $products->count() >= $limit
            || $batches->count() >= $limit
            || $containers->count() >= $limit;

        $notes = $notesRaw->map(function ($note): array {
            $arr = (array) $note;
            if (isset($arr['clinical_case_id']) && ! isset($arr['case_id'])) {
                $arr['case_id'] = $arr['clinical_case_id'];
            }

            return $arr;
        });

        $maxUpdatedAt = null;
        $collections = [$clients, $patients, $species, $cases, $visits, $notesRaw, $products, $batches, $containers];

        foreach ($collections as $collection) {
            if ($collection->isNotEmpty()) {
                $lastItem = $collection->last();
                $updatedAt = is_array($lastItem) ? ($lastItem['updated_at'] ?? null) : ($lastItem->updated_at ?? null);
                if ($updatedAt !== null) {
                    $updatedAtStr = (string) $updatedAt;
                    if ($maxUpdatedAt === null || $updatedAtStr > $maxUpdatedAt) {
                        $maxUpdatedAt = $updatedAtStr;
                    }
                }
            }
        }

        $nextSince = $maxUpdatedAt !== null ? Carbon::parse($maxUpdatedAt)->toIso8601String() : null;

        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'has_more' => $hasMore,
            'next_since' => $nextSince,
            'deltas' => [
                'clients' => $clients,
                'patients' => $patients,
                'species' => $species,
                'cases' => $cases,
                'visits' => $visits,
                'progress_notes' => $notes,
                'inventory_products' => $products,
                'inventory_batches' => $batches,
                'inventory_containers' => $containers,
            ],
        ]);
    }

    /**
     * Ingest client-side queued mutations asynchronously via queued worker job.
     */
    public function push(Request $request, TenantManager $tenantManager): JsonResponse
    {
        $mutations = $request->input('mutations', []);
        $batchId = (string) Str::uuid();
        $user = $request->user();
        $userId = (string) ($user?->id ?? '');

        $tenant = $tenantManager->current() ?? $request->attributes->get('tenant');
        $tenantId = $tenant?->id ?? (string) $request->header('X-Tenant-ID');

        ProcessSyncMutationsJob::dispatch($mutations, $userId, $batchId, $tenantId);

        return response()->json([
            'status' => 'accepted',
            'batch_id' => $batchId,
            'message' => 'Sync mutations accepted for processing.',
        ], Response::HTTP_ACCEPTED);
    }
}
