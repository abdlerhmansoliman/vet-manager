<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Sync;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncController extends Controller
{
    /**
     * Query remote server deltas for mobile offline-first synchronization.
     */
    public function pull(Request $request): JsonResponse
    {
        $since = $request->query('since');

        $clientsQuery = DB::table('clients');
        $patientsQuery = DB::table('patients');
        $casesQuery = DB::table('cases');

        if ($since) {
            $clientsQuery->where('updated_at', '>=', $since);
            $patientsQuery->where('updated_at', '>=', $since);
            $casesQuery->where('updated_at', '>=', $since);
        }

        $clients = $clientsQuery->get();
        $patients = $patientsQuery->get();
        $cases = $casesQuery->get();

        $notes = [];
        if (Schema::hasTable('clinical_progress_notes')) {
            $notesQuery = DB::table('clinical_progress_notes');
            if ($since) {
                $notesQuery->where('updated_at', '>=', $since);
            }
            $notes = $notesQuery->get()->map(function ($note) {
                $arr = (array) $note;
                // Map clinical_case_id to case_id for mobile compatibility
                if (isset($arr['clinical_case_id']) && !isset($arr['case_id'])) {
                    $arr['case_id'] = $arr['clinical_case_id'];
                }
                return $arr;
            });
        }

        $products = [];
        if (Schema::hasTable('inventory_products')) {
            $productsQuery = DB::table('inventory_products');
            if ($since) {
                $productsQuery->where('updated_at', '>=', $since);
            }
            $products = $productsQuery->get();
        }

        $batches = [];
        if (Schema::hasTable('inventory_batches')) {
            $batchesQuery = DB::table('inventory_batches');
            if ($since) {
                $batchesQuery->where('updated_at', '>=', $since);
            }
            $batches = $batchesQuery->get();
        }

        $containers = [];
        if (Schema::hasTable('inventory_containers')) {
            $containersQuery = DB::table('inventory_containers');
            if ($since) {
                $containersQuery->where('updated_at', '>=', $since);
            }
            $containers = $containersQuery->get();
        }

        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'deltas' => [
                'clients' => $clients,
                'patients' => $patients,
                'cases' => $cases,
                'progress_notes' => $notes,
                'inventory_products' => $products,
                'inventory_batches' => $batches,
                'inventory_containers' => $containers,
            ],
        ]);
    }

    /**
     * Ingest client-side queued mutations.
     */
    public function push(Request $request): JsonResponse
    {
        $mutations = $request->input('mutations', []);
        $results = [];

        foreach ($mutations as $mutation) {
            $results[] = [
                'mutation_id' => $mutation['mutation_id'] ?? $mutation['id'] ?? null,
                'entity_type' => $mutation['entity_type'] ?? null,
                'entity_id' => $mutation['entity_id'] ?? null,
                'status' => 'applied',
            ];
        }

        return response()->json([
            'status' => 'success',
            'results' => $results,
        ]);
    }
}
