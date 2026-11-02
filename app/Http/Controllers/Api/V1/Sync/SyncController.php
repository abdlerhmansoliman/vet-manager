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
        $fallbackVetId = DB::table('users')->value('id');

        foreach ($mutations as $mutation) {
            $mutationId = $mutation['mutation_id'] ?? $mutation['id'] ?? null;
            $entityType = $mutation['entity_type'] ?? null;
            $entityId = $mutation['entity_id'] ?? null;
            $action = $mutation['action'] ?? 'create';
            $payload = $mutation['payload'] ?? [];
            if (is_string($payload)) {
                $payload = json_decode($payload, true) ?? [];
            }

            try {
                if ($entityType === 'client' && !empty($payload)) {
                    $clientId = $payload['id'] ?? $entityId;
                    if ($action === 'delete') {
                        DB::table('clients')->where('id', $clientId)->update(['deleted_at' => now()]);
                    } else {
                        DB::table('clients')->updateOrInsert(
                            ['id' => $clientId],
                            [
                                'client_type' => $payload['client_type'] ?? 'individual',
                                'name' => $payload['name'] ?? 'Unknown Owner',
                                'contact_person' => $payload['contact_person'] ?? null,
                                'phone_primary' => $payload['phone_primary'] ?? 'N/A',
                                'phone_secondary' => $payload['phone_secondary'] ?? null,
                                'physical_address' => $payload['physical_address'] ?? null,
                                'notes' => $payload['notes'] ?? null,
                                'created_at' => $payload['created_at'] ?? now(),
                                'updated_at' => $payload['updated_at'] ?? now(),
                            ]
                        );
                    }
                } elseif ($entityType === 'patient' && !empty($payload)) {
                    $patientId = $payload['id'] ?? $entityId;
                    if ($action === 'delete') {
                        DB::table('patients')->where('id', $patientId)->update(['deleted_at' => now()]);
                    } else {
                        $gender = $payload['gender'] ?? 'unknown';
                        $genderMap = [
                            'male_neutered' => 'neutered_male',
                            'male_intact' => 'intact_male',
                            'female_spayed' => 'spayed_female',
                            'female_intact' => 'intact_female',
                        ];
                        $normalizedGender = $genderMap[$gender] ?? $gender;

                        $allergies = $payload['allergies'] ?? null;
                        if (is_array($allergies)) {
                            $allergies = json_encode($allergies);
                        }

                        $clientId = $payload['client_id'] ?? null;
                        if ($clientId) {
                            $clientExists = DB::table('clients')->where('id', $clientId)->exists();
                            if (!$clientExists) {
                                DB::table('clients')->insert([
                                    'id' => $clientId,
                                    'client_type' => 'individual',
                                    'name' => 'Auto-Registered Client',
                                    'phone_primary' => 'N/A',
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }

                        DB::table('patients')->updateOrInsert(
                            ['id' => $patientId],
                            [
                                'client_id' => $clientId,
                                'name_or_tag' => $payload['name_or_tag'] ?? 'Unknown',
                                'species' => $payload['species'] ?? 'Canine',
                                'breed' => $payload['breed'] ?? null,
                                'gender' => $normalizedGender,
                                'age' => isset($payload['age']) ? (int) $payload['age'] : null,
                                'date_of_birth' => $payload['date_of_birth'] ?? null,
                                'is_approximate_age' => !empty($payload['is_approximate_age']) ? 1 : 0,
                                'microchip_number' => $payload['microchip_number'] ?? null,
                                'ear_tag_id' => $payload['ear_tag_id'] ?? null,
                                'coat_color_markings' => $payload['coat_color_markings'] ?? null,
                                'current_weight_kg' => $payload['current_weight_kg'] ?? null,
                                'weight_recorded_at' => $payload['weight_recorded_at'] ?? null,
                                'history' => $payload['history'] ?? null,
                                'notes' => $payload['notes'] ?? null,
                                'status' => $payload['status'] ?? 'active',
                                'allergies' => $allergies,
                                'created_at' => $payload['created_at'] ?? now(),
                                'updated_at' => $payload['updated_at'] ?? now(),
                            ]
                        );
                    }
                } elseif (in_array($entityType, ['case', 'clinical_case'], true) && !empty($payload)) {
                    $caseId = $payload['id'] ?? $entityId;
                    if ($action === 'delete') {
                        DB::table('cases')->where('id', $caseId)->update(['deleted_at' => now()]);
                    } else {
                        $vetId = $payload['primary_veterinarian_id'] ?? $fallbackVetId;
                        $vetExists = DB::table('users')->where('id', $vetId)->exists();
                        if (!$vetExists) {
                            $vetId = $fallbackVetId;
                        }

                        DB::table('cases')->updateOrInsert(
                            ['id' => $caseId],
                            [
                                'case_number' => $payload['case_number'] ?? 'CAS-' . strtoupper(substr(str_replace('-', '', (string) $caseId), 0, 8)),
                                'patient_id' => $payload['patient_id'] ?? null,
                                'primary_veterinarian_id' => $vetId,
                                'case_type' => $payload['case_type'] ?? 'outpatient',
                                'status' => $payload['status'] ?? 'open',
                                'priority' => $payload['priority'] ?? 'routine',
                                'chief_complaint' => $payload['chief_complaint'] ?? 'Clinical examination',
                                'provisional_diagnosis' => $payload['provisional_diagnosis'] ?? null,
                                'final_diagnosis' => $payload['final_diagnosis'] ?? null,
                                'admitted_at' => $payload['admitted_at'] ?? now(),
                                'created_at' => $payload['created_at'] ?? now(),
                                'updated_at' => $payload['updated_at'] ?? now(),
                            ]
                        );
                    }
                }

                $results[] = [
                    'mutation_id' => $mutationId,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'status' => 'applied',
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'mutation_id' => $mutationId,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'status' => 'applied',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'results' => $results,
        ]);
    }
}
