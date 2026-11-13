<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\StockMovementType;
use App\Events\SyncBatchCompletedEvent;
use App\Models\InventoryProduct;
use App\Models\User;
use App\Services\Inventory\BatchDeductionService;
use App\Services\Tenancy\TenantManager;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProcessSyncMutationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  array<int, array<string, mixed>>  $mutations
     */
    public function __construct(
        public readonly array $mutations,
        public readonly string $userId,
        public readonly string $batchId,
        public readonly ?string $tenantId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TenantManager $tenantManager): void
    {
        try {
            if ($this->tenantId !== null && $this->tenantId !== '') {
                $tenant = $tenantManager->resolve($this->tenantId);
                if ($tenant !== null) {
                    $tenantManager->switch($tenant);
                }
            }

            $allResults = [];
            $chunks = array_chunk($this->mutations, 200);
            $tenantKey = $this->tenantId ?? 'global';

            foreach ($chunks as $chunk) {
                // 1. Pre-fetch existing IDs for this chunk to prevent N+1 queries
                $clientIds = [];
                $patientIds = [];
                $caseIds = [];
                $vetIds = [];

                foreach ($chunk as $mutation) {
                    $payload = $mutation['payload'] ?? [];
                    if (is_string($payload)) {
                        $payload = json_decode($payload, true) ?? [];
                    }

                    $entityType = $mutation['entity_type'] ?? null;
                    $entityId = $mutation['entity_id'] ?? null;
                    $targetId = $payload['id'] ?? $entityId;

                    if (in_array($entityType, ['client', 'clients'], true)) {
                        if ($targetId) {
                            $clientIds[] = (string) $targetId;
                        }
                    } elseif (in_array($entityType, ['patient', 'patients'], true)) {
                        if ($targetId) {
                            $patientIds[] = (string) $targetId;
                        }
                        if (! empty($payload['client_id'])) {
                            $clientIds[] = (string) $payload['client_id'];
                        }
                    } elseif (in_array($entityType, ['case', 'cases', 'clinical_case', 'clinical_cases'], true)) {
                        if ($targetId) {
                            $caseIds[] = (string) $targetId;
                        }
                        if (! empty($payload['patient_id'])) {
                            $patientIds[] = (string) $payload['patient_id'];
                        }
                        $vet = $payload['primary_veterinarian_id'] ?? $payload['veterinarian_id'] ?? $payload['vet_id'] ?? null;
                        if (! empty($vet)) {
                            $vetIds[] = (string) $vet;
                        }
                    } elseif (in_array($entityType, ['progress_note', 'progress_notes', 'clinical_progress_note', 'clinical_progress_notes'], true)) {
                        $case = $payload['clinical_case_id'] ?? $payload['case_id'] ?? null;
                        if (! empty($case)) {
                            $caseIds[] = (string) $case;
                        }
                        $vet = $payload['vet_id'] ?? $payload['veterinarian_id'] ?? $payload['primary_veterinarian_id'] ?? null;
                        if (! empty($vet)) {
                            $vetIds[] = (string) $vet;
                        }
                    } elseif (in_array($entityType, ['visit', 'visits'], true)) {
                        $case = $payload['case_id'] ?? null;
                        if (! empty($case)) {
                            $caseIds[] = (string) $case;
                        }
                    }
                }

                $filterIds = fn (array $ids): array => array_values(array_unique(array_filter($ids, fn ($id) => is_string($id) && $id !== '')));

                $clientIds = $filterIds($clientIds);
                $patientIds = $filterIds($patientIds);
                $caseIds = $filterIds($caseIds);
                $vetIds = $filterIds($vetIds);

                $existingClientIds = ! empty($clientIds)
                    ? array_fill_keys(DB::table('clients')->whereIn('id', $clientIds)->pluck('id')->all(), true)
                    : [];

                $existingPatientIds = ! empty($patientIds)
                    ? array_fill_keys(DB::table('patients')->whereIn('id', $patientIds)->pluck('id')->all(), true)
                    : [];

                $existingCaseIds = ! empty($caseIds)
                    ? array_fill_keys(DB::table('cases')->whereIn('id', $caseIds)->pluck('id')->all(), true)
                    : [];

                $existingVetIds = ! empty($vetIds)
                    ? array_fill_keys(DB::table('users')->whereIn('id', $vetIds)->pluck('id')->all(), true)
                    : [];

                $fallbackVetId = (string) DB::table('users')->value('id');
                if ($fallbackVetId !== '') {
                    $existingVetIds[$fallbackVetId] = true;
                }

                // 2. Wrap the processing of each chunk in its own DB::transaction()
                try {
                    $chunkResults = DB::transaction(function () use (
                        $chunk,
                        $fallbackVetId,
                        $tenantKey,
                        &$existingClientIds,
                        &$existingPatientIds,
                        &$existingCaseIds,
                        &$existingVetIds
                    ): array {
                        $results = [];

                        foreach ($chunk as $mutation) {
                            $mutationId = $mutation['mutation_id'] ?? $mutation['id'] ?? null;
                            $entityType = $mutation['entity_type'] ?? null;
                            $entityId = $mutation['entity_id'] ?? null;
                            $action = $mutation['action'] ?? 'create';
                            $payload = $mutation['payload'] ?? [];
                            if (is_string($payload)) {
                                $payload = json_decode($payload, true) ?? [];
                            }

                            $targetId = $payload['id'] ?? $entityId;

                            if (! is_string($targetId) || $targetId === '') {
                                $results[] = [
                                    'mutation_id' => $mutationId,
                                    'entity_type' => $entityType,
                                    'entity_id' => $entityId,
                                    'status' => 'failed',
                                    'message' => 'Missing or invalid entity ID in mutation payload.',
                                ];

                                continue;
                            }

                            // Idempotency check: deduplicate mutations per tenant using idempotency_key
                            $idempotencyKey = $mutation['idempotency_key'] ?? null;
                            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                                $cacheKey = "sync:mutation:{$tenantKey}:{$idempotencyKey}";
                                if (Cache::has($cacheKey)) {
                                    $results[] = [
                                        'mutation_id' => $mutationId,
                                        'entity_type' => $entityType,
                                        'entity_id' => $entityId,
                                        'status' => 'applied',
                                        'message' => 'Idempotent replay: mutation already applied.',
                                    ];

                                    continue;
                                }
                            }

                            try {
                                match ($entityType) {
                                    'client', 'clients' => $this->applyClientMutation($action, $targetId, $payload, $existingClientIds),
                                    'patient', 'patients' => $this->applyPatientMutation($action, $targetId, $payload, $existingClientIds, $existingPatientIds),
                                    'case', 'cases', 'clinical_case', 'clinical_cases' => $this->applyCaseMutation($action, $targetId, $payload, $fallbackVetId, $existingClientIds, $existingPatientIds, $existingCaseIds, $existingVetIds),
                                    'visit', 'visits' => $this->applyVisitMutation($action, $targetId, $payload, $fallbackVetId, $existingClientIds, $existingPatientIds, $existingCaseIds),
                                    'progress_note', 'progress_notes', 'clinical_progress_note', 'clinical_progress_notes' => $this->applyProgressNoteMutation($action, $targetId, $payload, $fallbackVetId, $existingClientIds, $existingPatientIds, $existingCaseIds, $existingVetIds),
                                    'dose', 'doses', 'dose_administration', 'dose_administrations' => $this->applyDoseMutation($action, $targetId, $payload, $fallbackVetId),
                                    'species' => $this->applySpeciesMutation($action, $targetId, $payload),
                                    default => throw new \InvalidArgumentException("Unsupported entity type: {$entityType}"),
                                };

                                if ($idempotencyKey !== null && $idempotencyKey !== '') {
                                    Cache::put("sync:mutation:{$tenantKey}:{$idempotencyKey}", true, now()->addDays(7));
                                }

                                $results[] = [
                                    'mutation_id' => $mutationId,
                                    'entity_type' => $entityType,
                                    'entity_id' => $entityId,
                                    'status' => 'applied',
                                ];
                            } catch (\DomainException $e) {
                                $results[] = [
                                    'mutation_id' => $mutationId,
                                    'entity_type' => $entityType,
                                    'entity_id' => $entityId,
                                    'status' => 'rejected',
                                    'message' => $e->getMessage(),
                                ];
                            } catch (\InvalidArgumentException $e) {
                                $results[] = [
                                    'mutation_id' => $mutationId,
                                    'entity_type' => $entityType,
                                    'entity_id' => $entityId,
                                    'status' => 'ignored',
                                    'message' => $e->getMessage(),
                                ];
                            }
                        }

                        return $results;
                    });

                    foreach ($chunkResults as $res) {
                        $allResults[] = $res;
                    }
                } catch (\Throwable $e) {
                    // Rollback ONLY that chunk if it fails, and record failed status so the next chunk can proceed
                    foreach ($chunk as $mutation) {
                        $allResults[] = [
                            'mutation_id' => $mutation['mutation_id'] ?? $mutation['id'] ?? null,
                            'entity_type' => $mutation['entity_type'] ?? null,
                            'entity_id' => $mutation['entity_id'] ?? null,
                            'status' => 'failed',
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            }

            // 3. Fire broadcast event passing the batch ID and final results array
            SyncBatchCompletedEvent::dispatch($this->batchId, $allResults);
        } finally {
            $tenantManager->reset();
        }
    }

    /**
     * Ingest client mutation via atomic upsert.
     *
     * @param  array<string, bool>  $existingClientIds
     */
    /**
     * Ingest animal species mutation via atomic upsert.
     */
    private function applySpeciesMutation(string $action, string $speciesId, array $payload): void
    {
        $existing = DB::table('species')->where('id', $speciesId)->first();

        $incomingUpdatedAt = ! empty($payload['updated_at']) ? Carbon::parse($payload['updated_at']) : null;
        if ($existing && $incomingUpdatedAt && Carbon::parse($existing->updated_at)->isAfter($incomingUpdatedAt)) {
            throw new \InvalidArgumentException("Server species record is newer than incoming mutation. Mutation ignored under LWW.");
        }

        if ($action === 'delete') {
            DB::table('species')->where('id', $speciesId)->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $now = now();
        $name = trim((string) ($payload['name'] ?? 'Unknown Species'));
        $code = isset($payload['code']) && trim((string) $payload['code']) !== ''
            ? Str::slug((string) $payload['code'], '_')
            : Str::slug($name, '_');

        DB::table('species')->upsert(
            [
                'id' => $speciesId,
                'name' => $name,
                'code' => $code,
                'icon' => $payload['icon'] ?? null,
                'is_system_default' => (bool) ($payload['is_system_default'] ?? false),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'created_at' => $payload['created_at'] ?? $now,
                'updated_at' => $payload['updated_at'] ?? $now,
            ],
            ['id'],
            [
                'name',
                'code',
                'icon',
                'is_active',
                'updated_at',
            ]
        );
    }

    private function applyClientMutation(string $action, string $clientId, array $payload, array &$existingClientIds): void
    {
        $existing = DB::table('clients')->where('id', $clientId)->first();

        $incomingUpdatedAt = ! empty($payload['updated_at']) ? Carbon::parse($payload['updated_at']) : null;
        if ($existing && $incomingUpdatedAt && Carbon::parse($existing->updated_at)->isAfter($incomingUpdatedAt)) {
            throw new \InvalidArgumentException("Server client record is newer than incoming mutation. Mutation ignored under LWW.");
        }

        if ($action === 'delete') {
            DB::table('clients')->where('id', $clientId)->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $now = now();

        DB::table('clients')->upsert(
            [
                'id' => $clientId,
                'client_type' => $payload['client_type'] ?? 'individual',
                'name' => $payload['name'] ?? 'Unknown Owner',
                'contact_person' => $payload['contact_person'] ?? null,
                'phone_primary' => $payload['phone_primary'] ?? 'N/A',
                'phone_secondary' => $payload['phone_secondary'] ?? null,
                'physical_address' => $payload['physical_address'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'created_at' => $payload['created_at'] ?? $now,
                'updated_at' => $payload['updated_at'] ?? $now,
            ],
            ['id'],
            [
                'client_type',
                'name',
                'contact_person',
                'phone_primary',
                'phone_secondary',
                'physical_address',
                'notes',
                'updated_at',
            ]
        );

        $existingClientIds[$clientId] = true;
    }

    /**
     * Ingest patient mutation via atomic upsert with auto-provisioned client fallback.
     *
     * @param  array<string, bool>  $existingClientIds
     * @param  array<string, bool>  $existingPatientIds
     */
    private function applyPatientMutation(
        string $action,
        string $patientId,
        array $payload,
        array &$existingClientIds,
        array &$existingPatientIds
    ): void {
        $existing = DB::table('patients')->where('id', $patientId)->first();

        $incomingUpdatedAt = ! empty($payload['updated_at']) ? Carbon::parse($payload['updated_at']) : null;
        if ($existing && $incomingUpdatedAt && Carbon::parse($existing->updated_at)->isAfter($incomingUpdatedAt)) {
            throw new \InvalidArgumentException("Server patient record is newer than incoming mutation. Mutation ignored under LWW.");
        }

        if ($action === 'delete') {
            DB::table('patients')->where('id', $patientId)->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $now = now();
        $clientId = $payload['client_id'] ?? null;

        // Auto-provision placeholder client if missing to satisfy foreign key constraints
        if ($clientId && ! isset($existingClientIds[$clientId])) {
            DB::table('clients')->upsert(
                [
                    'id' => $clientId,
                    'client_type' => 'individual',
                    'name' => 'Auto-Registered Client',
                    'phone_primary' => 'N/A',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['id'],
                ['updated_at']
            );
            $existingClientIds[$clientId] = true;
        } elseif (! $clientId) {
            $fallbackClientId = ! empty($existingClientIds) ? (string) array_key_first($existingClientIds) : (string) DB::table('clients')->value('id');
            if ($fallbackClientId === '') {
                $fallbackClientId = (string) Str::uuid();
                DB::table('clients')->upsert(
                    [
                        'id' => $fallbackClientId,
                        'client_type' => 'individual',
                        'name' => 'Auto-Registered Client',
                        'phone_primary' => 'N/A',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    ['id'],
                    ['updated_at']
                );
            }
            $clientId = $fallbackClientId;
            $existingClientIds[$clientId] = true;
        }

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

        DB::table('patients')->upsert(
            [
                'id' => $patientId,
                'client_id' => $clientId,
                'name_or_tag' => $payload['name_or_tag'] ?? 'Unknown',
                'species' => $payload['species'] ?? 'Canine',
                'breed' => $payload['breed'] ?? null,
                'gender' => $normalizedGender,
                'age' => isset($payload['age']) ? (int) $payload['age'] : null,
                'date_of_birth' => $payload['date_of_birth'] ?? null,
                'is_approximate_age' => ! empty($payload['is_approximate_age']) ? 1 : 0,
                'microchip_number' => $payload['microchip_number'] ?? null,
                'ear_tag_id' => $payload['ear_tag_id'] ?? null,
                'coat_color_markings' => $payload['coat_color_markings'] ?? null,
                'current_weight_kg' => $payload['current_weight_kg'] ?? null,
                'weight_recorded_at' => $payload['weight_recorded_at'] ?? null,
                'history' => $payload['history'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'status' => $payload['status'] ?? 'active',
                'allergies' => $allergies,
                'created_at' => $payload['created_at'] ?? $now,
                'updated_at' => $payload['updated_at'] ?? $now,
            ],
            ['id'],
            [
                'client_id',
                'name_or_tag',
                'species',
                'breed',
                'gender',
                'age',
                'date_of_birth',
                'is_approximate_age',
                'microchip_number',
                'ear_tag_id',
                'coat_color_markings',
                'current_weight_kg',
                'weight_recorded_at',
                'history',
                'notes',
                'status',
                'allergies',
                'updated_at',
            ]
        );

        $existingPatientIds[$patientId] = true;
    }

    /**
     * Ingest clinical case mutation via atomic upsert with auto-provisioned patient fallback.
     *
     * @param  array<string, bool>  $existingClientIds
     * @param  array<string, bool>  $existingPatientIds
     * @param  array<string, bool>  $existingCaseIds
     * @param  array<string, bool>  $existingVetIds
     */
    private function applyCaseMutation(
        string $action,
        string $caseId,
        array $payload,
        string $fallbackVetId,
        array &$existingClientIds,
        array &$existingPatientIds,
        array &$existingCaseIds,
        array &$existingVetIds
    ): void {
        $existing = DB::table('cases')->where('id', $caseId)->first();

        $incomingUpdatedAt = ! empty($payload['updated_at']) ? Carbon::parse($payload['updated_at']) : null;
        if ($existing && $incomingUpdatedAt && Carbon::parse($existing->updated_at)->isAfter($incomingUpdatedAt)) {
            throw new \InvalidArgumentException("Server case record is newer than incoming mutation. Mutation ignored under LWW.");
        }

        if ($action === 'delete') {
            DB::table('cases')->where('id', $caseId)->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $now = now();

        // Resolve primary veterinarian
        $vetId = $payload['primary_veterinarian_id'] ?? $payload['veterinarian_id'] ?? $payload['vet_id'] ?? null;
        if (! $vetId || ! isset($existingVetIds[$vetId])) {
            $vetId = $fallbackVetId;
        }

        // Auto-provision placeholder patient if missing (Requirement 4)
        $patientId = $payload['patient_id'] ?? null;
        if ($patientId && ! isset($existingPatientIds[$patientId])) {
            $clientForPatient = ! empty($existingClientIds) ? (string) array_key_first($existingClientIds) : (string) DB::table('clients')->value('id');
            if ($clientForPatient === '') {
                $clientForPatient = (string) Str::uuid();
                DB::table('clients')->upsert(
                    [
                        'id' => $clientForPatient,
                        'client_type' => 'individual',
                        'name' => 'Auto-Registered Client',
                        'phone_primary' => 'N/A',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    ['id'],
                    ['updated_at']
                );
                $existingClientIds[$clientForPatient] = true;
            }

            DB::table('patients')->upsert(
                [
                    'id' => $patientId,
                    'client_id' => $clientForPatient,
                    'name_or_tag' => 'Auto-Registered Patient',
                    'species' => 'Canine',
                    'gender' => 'unknown',
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['id'],
                ['updated_at']
            );
            $existingPatientIds[$patientId] = true;
        } elseif (! $patientId) {
            $fallbackPatientId = ! empty($existingPatientIds) ? (string) array_key_first($existingPatientIds) : (string) DB::table('patients')->value('id');
            if ($fallbackPatientId === '') {
                $fallbackPatientId = (string) Str::uuid();
                $clientForPatient = ! empty($existingClientIds) ? (string) array_key_first($existingClientIds) : (string) DB::table('clients')->value('id');
                if ($clientForPatient === '') {
                    $clientForPatient = (string) Str::uuid();
                    DB::table('clients')->upsert(
                        [
                            'id' => $clientForPatient,
                            'client_type' => 'individual',
                            'name' => 'Auto-Registered Client',
                            'phone_primary' => 'N/A',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        ['id'],
                        ['updated_at']
                    );
                    $existingClientIds[$clientForPatient] = true;
                }

                DB::table('patients')->upsert(
                    [
                        'id' => $fallbackPatientId,
                        'client_id' => $clientForPatient,
                        'name_or_tag' => 'Auto-Registered Patient',
                        'species' => 'Canine',
                        'gender' => 'unknown',
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    ['id'],
                    ['updated_at']
                );
                $existingPatientIds[$fallbackPatientId] = true;
            }
            $patientId = $fallbackPatientId;
        }

        $caseNumber = $payload['case_number'] ?? 'CAS-'.strtoupper(substr(str_replace('-', '', (string) $caseId), 0, 8));

        DB::table('cases')->upsert(
            [
                'id' => $caseId,
                'case_number' => $caseNumber,
                'patient_id' => $patientId,
                'primary_veterinarian_id' => $vetId,
                'case_type' => $payload['case_type'] ?? 'outpatient',
                'status' => $payload['status'] ?? 'open',
                'priority' => $payload['priority'] ?? 'routine',
                'chief_complaint' => $payload['chief_complaint'] ?? 'Clinical examination',
                'provisional_diagnosis' => $payload['provisional_diagnosis'] ?? null,
                'final_diagnosis' => $payload['final_diagnosis'] ?? null,
                'admitted_at' => $payload['admitted_at'] ?? $now,
                'created_at' => $payload['created_at'] ?? $now,
                'updated_at' => $payload['updated_at'] ?? $now,
            ],
            ['id'],
            [
                'patient_id',
                'primary_veterinarian_id',
                'case_type',
                'status',
                'priority',
                'chief_complaint',
                'provisional_diagnosis',
                'final_diagnosis',
                'admitted_at',
                'updated_at',
            ]
        );

        $existingCaseIds[$caseId] = true;
    }

    /**
     * Ingest clinical progress note mutation via atomic upsert with auto-provisioned case fallback.
     *
     * @param  array<string, bool>  $existingClientIds
     * @param  array<string, bool>  $existingPatientIds
     * @param  array<string, bool>  $existingCaseIds
     * @param  array<string, bool>  $existingVetIds
     */
    private function applyProgressNoteMutation(
        string $action,
        string $noteId,
        array $payload,
        string $fallbackVetId,
        array &$existingClientIds,
        array &$existingPatientIds,
        array &$existingCaseIds,
        array &$existingVetIds
    ): void {
        $visitId = $payload['visit_id'] ?? null;
        if ($visitId && Schema::connection('tenant')->hasTable('visits')) {
            $visit = DB::table('visits')->where('id', $visitId)->first();
            if ($visit && ! empty($visit->is_signed)) {
                throw new \DomainException("Associated visit {$visitId} is signed and locked (is_signed=true). Progress note modifications are strictly rejected.");
            }
        }

        $existingNote = DB::table('clinical_progress_notes')->where('id', $noteId)->first();
        if ($existingNote && ! empty($existingNote->is_signed)) {
            throw new \DomainException("Progress note {$noteId} is signed and locked (is_signed=true). Modifications are strictly rejected.");
        }

        $incomingUpdatedAt = ! empty($payload['updated_at']) ? Carbon::parse($payload['updated_at']) : null;
        if ($existingNote && $incomingUpdatedAt && Carbon::parse($existingNote->updated_at)->isAfter($incomingUpdatedAt)) {
            throw new \InvalidArgumentException("Server progress note record is newer than incoming mutation. Mutation ignored under LWW.");
        }

        if ($action === 'delete') {
            DB::table('clinical_progress_notes')->where('id', $noteId)->delete();

            return;
        }

        $now = now();
        $caseId = $payload['clinical_case_id'] ?? $payload['case_id'] ?? null;

        // Auto-provision case if missing to satisfy foreign key constraint
        if ($caseId && ! isset($existingCaseIds[$caseId])) {
            $casePatientId = ! empty($existingPatientIds) ? (string) array_key_first($existingPatientIds) : (string) DB::table('patients')->value('id');
            if ($casePatientId === '') {
                $casePatientId = (string) Str::uuid();
                $clientForPatient = ! empty($existingClientIds) ? (string) array_key_first($existingClientIds) : (string) DB::table('clients')->value('id');
                if ($clientForPatient === '') {
                    $clientForPatient = (string) Str::uuid();
                    DB::table('clients')->upsert(
                        [
                            'id' => $clientForPatient,
                            'client_type' => 'individual',
                            'name' => 'Auto-Registered Client',
                            'phone_primary' => 'N/A',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        ['id'],
                        ['updated_at']
                    );
                    $existingClientIds[$clientForPatient] = true;
                }

                DB::table('patients')->upsert(
                    [
                        'id' => $casePatientId,
                        'client_id' => $clientForPatient,
                        'name_or_tag' => 'Auto-Registered Patient',
                        'species' => 'Canine',
                        'gender' => 'unknown',
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    ['id'],
                    ['updated_at']
                );
                $existingPatientIds[$casePatientId] = true;
            }

            DB::table('cases')->upsert(
                [
                    'id' => $caseId,
                    'case_number' => 'CAS-'.strtoupper(substr(str_replace('-', '', (string) $caseId), 0, 8)),
                    'patient_id' => $casePatientId,
                    'primary_veterinarian_id' => $fallbackVetId,
                    'case_type' => 'outpatient',
                    'status' => 'open',
                    'priority' => 'routine',
                    'chief_complaint' => 'Clinical examination',
                    'admitted_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['id'],
                ['updated_at']
            );
            $existingCaseIds[$caseId] = true;
        }

        // Resolve veterinarian ID
        $vetId = $payload['vet_id'] ?? $payload['veterinarian_id'] ?? $payload['primary_veterinarian_id'] ?? null;
        if (! $vetId || ! isset($existingVetIds[$vetId])) {
            $vetId = $fallbackVetId;
        }

        $isSigned = ! empty($payload['is_signed']);
        $signedAt = $payload['signed_at'] ?? ($isSigned ? $now : null);

        $subjective = is_array($payload['subjective_notes'] ?? null) ? json_encode($payload['subjective_notes']) : ($payload['subjective_notes'] ?? null);
        $objective = is_array($payload['objective_notes'] ?? null) ? json_encode($payload['objective_notes']) : ($payload['objective_notes'] ?? null);
        $assessment = is_array($payload['assessment_notes'] ?? null) ? json_encode($payload['assessment_notes']) : ($payload['assessment_notes'] ?? null);
        $plan = is_array($payload['plan_notes'] ?? null) ? json_encode($payload['plan_notes']) : ($payload['plan_notes'] ?? null);

        $insertData = [
            'id' => $noteId,
            'clinical_case_id' => $caseId,
            'vet_id' => $vetId,
            'temperature_c' => $payload['temperature_c'] ?? null,
            'heart_rate_bpm' => isset($payload['heart_rate_bpm']) ? (int) $payload['heart_rate_bpm'] : null,
            'respiratory_rate' => isset($payload['respiratory_rate']) ? (int) $payload['respiratory_rate'] : null,
            'systolic_bp' => isset($payload['systolic_bp']) ? (int) $payload['systolic_bp'] : null,
            'diastolic_bp' => isset($payload['diastolic_bp']) ? (int) $payload['diastolic_bp'] : null,
            'mucous_membrane' => $payload['mucous_membrane'] ?? null,
            'weight_kg' => isset($payload['weight_kg']) ? (float) $payload['weight_kg'] : null,
            'subjective_notes' => $subjective,
            'objective_notes' => $objective,
            'assessment_notes' => $assessment,
            'plan_notes' => $plan,
            'is_signed' => $isSigned ? 1 : 0,
            'signed_at' => $signedAt,
            'created_at' => $payload['created_at'] ?? $now,
            'updated_at' => $payload['updated_at'] ?? $now,
        ];

        $updateColumns = [
            'clinical_case_id',
            'vet_id',
            'temperature_c',
            'heart_rate_bpm',
            'respiratory_rate',
            'systolic_bp',
            'diastolic_bp',
            'mucous_membrane',
            'weight_kg',
            'subjective_notes',
            'objective_notes',
            'assessment_notes',
            'plan_notes',
            'is_signed',
            'signed_at',
            'updated_at',
        ];

        if (Schema::connection('tenant')->hasColumn('clinical_progress_notes', 'visit_id')) {
            $insertData['visit_id'] = $visitId;
            $updateColumns[] = 'visit_id';
        }

        DB::table('clinical_progress_notes')->upsert(
            $insertData,
            ['id'],
            $updateColumns
        );

        // Synchronize patient weight if provided
        if (isset($payload['weight_kg']) && $caseId) {
            $patientIdForCase = DB::table('cases')->where('id', $caseId)->value('patient_id');
            if ($patientIdForCase) {
                DB::table('patients')->where('id', $patientIdForCase)->update([
                    'current_weight_kg' => (string) $payload['weight_kg'],
                    'weight_recorded_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Ingest clinical visit mutation via atomic upsert with auto-provisioned case fallback.
     *
     * @param  array<string, bool>  $existingClientIds
     * @param  array<string, bool>  $existingPatientIds
     * @param  array<string, bool>  $existingCaseIds
     */
    private function applyVisitMutation(
        string $action,
        string $visitId,
        array $payload,
        string $fallbackVetId,
        array &$existingClientIds,
        array &$existingPatientIds,
        array &$existingCaseIds
    ): void {
        if (! Schema::connection('tenant')->hasTable('visits')) {
            return;
        }

        $existingVisit = DB::table('visits')->where('id', $visitId)->first();
        if ($existingVisit && ! empty($existingVisit->is_signed)) {
            throw new \DomainException("Clinical visit {$visitId} is signed and locked (is_signed=true). Modifications and deletions are strictly rejected.");
        }

        $incomingUpdatedAt = ! empty($payload['updated_at']) ? Carbon::parse($payload['updated_at']) : null;
        if ($existingVisit && $incomingUpdatedAt && Carbon::parse($existingVisit->updated_at)->isAfter($incomingUpdatedAt)) {
            throw new \InvalidArgumentException("Server visit record is newer than incoming mutation. Mutation ignored under LWW.");
        }

        if ($action === 'delete') {
            if (Schema::connection('tenant')->hasColumn('visits', 'deleted_at')) {
                DB::table('visits')->where('id', $visitId)->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('visits')->where('id', $visitId)->delete();
            }

            return;
        }

        $now = now();
        $caseId = $payload['case_id'] ?? null;

        // Auto-provision case if missing to satisfy foreign key constraint
        if ($caseId && ! isset($existingCaseIds[$caseId])) {
            $casePatientId = ! empty($existingPatientIds) ? (string) array_key_first($existingPatientIds) : (string) DB::table('patients')->value('id');
            if ($casePatientId === '') {
                $casePatientId = (string) Str::uuid();
                $clientForPatient = ! empty($existingClientIds) ? (string) array_key_first($existingClientIds) : (string) DB::table('clients')->value('id');
                if ($clientForPatient === '') {
                    $clientForPatient = (string) Str::uuid();
                    DB::table('clients')->upsert(
                        [
                            'id' => $clientForPatient,
                            'client_type' => 'individual',
                            'name' => 'Auto-Registered Client',
                            'phone_primary' => 'N/A',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                        ['id'],
                        ['updated_at']
                    );
                    $existingClientIds[$clientForPatient] = true;
                }

                DB::table('patients')->upsert(
                    [
                        'id' => $casePatientId,
                        'client_id' => $clientForPatient,
                        'name_or_tag' => 'Auto-Registered Patient',
                        'species' => 'Canine',
                        'gender' => 'unknown',
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    ['id'],
                    ['updated_at']
                );
                $existingPatientIds[$casePatientId] = true;
            }

            DB::table('cases')->upsert(
                [
                    'id' => $caseId,
                    'case_number' => 'CAS-'.strtoupper(substr(str_replace('-', '', (string) $caseId), 0, 8)),
                    'patient_id' => $casePatientId,
                    'primary_veterinarian_id' => $fallbackVetId,
                    'case_type' => 'outpatient',
                    'status' => 'open',
                    'priority' => 'routine',
                    'chief_complaint' => 'Clinical Encounter',
                    'admitted_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['id'],
                ['updated_at']
            );
            $existingCaseIds[$caseId] = true;
        }

        if (! $caseId) {
            $caseId = ! empty($existingCaseIds) ? (string) array_key_first($existingCaseIds) : (string) DB::table('cases')->value('id');
        }

        DB::table('visits')->upsert(
            [
                'id' => $visitId,
                'case_id' => $caseId,
                'title' => $payload['title'] ?? 'Visit',
                'visit_type' => $payload['visit_type'] ?? 'outpatient',
                'status' => $payload['status'] ?? 'open',
                'is_signed' => (bool) ($payload['is_signed'] ?? false) ? 1 : 0,
                'signed_at' => $payload['signed_at'] ?? null,
                'signed_by' => $payload['signed_by'] ?? null,
                'reason_for_visit' => $payload['reason_for_visit'] ?? null,
                'visit_date' => $payload['visit_date'] ?? $now,
                'created_at' => $payload['created_at'] ?? $now,
                'updated_at' => $payload['updated_at'] ?? $now,
            ],
            ['id'],
            [
                'title',
                'visit_type',
                'status',
                'is_signed',
                'signed_at',
                'signed_by',
                'reason_for_visit',
                'visit_date',
                'updated_at',
            ]
        );
    }

    /**
     * Ingest dose administration mutation via atomic insert and batch deduction.
     */
    private function applyDoseMutation(string $action, string $doseId, array $payload, string $fallbackVetId): void
    {
        if (! Schema::connection('tenant')->hasTable('dose_administrations')) {
            return;
        }

        $existing = DB::table('dose_administrations')->where('id', $doseId)->first();

        $incomingUpdatedAt = ! empty($payload['updated_at']) ? Carbon::parse($payload['updated_at']) : null;
        if ($existing && $incomingUpdatedAt && Carbon::parse($existing->updated_at)->isAfter($incomingUpdatedAt)) {
            throw new \InvalidArgumentException("Server dose record is newer than incoming mutation. Mutation ignored under LWW.");
        }

        if ($action === 'delete') {
            DB::table('dose_administrations')->where('id', $doseId)->delete();

            return;
        }

        $now = now();
        $caseId = $payload['case_id'] ?? null;
        $patientId = $payload['patient_id'] ?? null;
        if (! $patientId && $caseId) {
            $patientId = DB::table('cases')->where('id', $caseId)->value('patient_id');
        }
        $productId = $payload['product_id'] ?? null;
        $administeredBy = $payload['administered_by_user_id'] ?? $payload['vet_id'] ?? $fallbackVetId;
        $doseAmount = (string) ($payload['dose_amount_base_units'] ?? $payload['dose_amount'] ?? '1');
        $route = $payload['route_administered'] ?? null;
        $notes = $payload['clinical_notes'] ?? null;

        if ($existing) {
            // Record already exists; update metadata without re-deducting inventory
            DB::table('dose_administrations')->where('id', $doseId)->update([
                'route_administered' => $route,
                'clinical_notes' => $notes,
                'updated_at' => $payload['updated_at'] ?? $now,
            ]);

            return;
        }

        DB::table('dose_administrations')->insert([
            'id' => $doseId,
            'case_id' => $caseId,
            'patient_id' => $patientId,
            'product_id' => $productId,
            'administered_by_user_id' => $administeredBy,
            'dose_amount_base_units' => $doseAmount,
            'route_administered' => $route,
            'clinical_notes' => $notes,
            'created_at' => $payload['created_at'] ?? $now,
            'updated_at' => $payload['updated_at'] ?? $now,
        ]);

        // If product exists, perform batch deduction with allowNegativeBalance: true
        if ($productId) {
            $product = InventoryProduct::find($productId);
            $user = User::find($administeredBy) ?? User::find($fallbackVetId);
            if ($product && $user) {
                $deductionService = app(BatchDeductionService::class);
                $reason = "Offline sync dose administration {$doseId}";
                $deductionService->deduct(
                    product: $product,
                    doseAmountBaseUnits: $doseAmount,
                    actor: $user,
                    movementType: StockMovementType::DoseAdministration,
                    reason: $reason,
                    idempotencyKey: "sync:dose:{$doseId}",
                    allowNegativeBalance: true
                );
            }
        }
    }

}
