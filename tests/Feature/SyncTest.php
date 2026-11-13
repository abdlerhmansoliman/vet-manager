<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\SyncBatchCompletedEvent;
use App\Jobs\ProcessSyncMutationsJob;
use App\Models\Client;
use App\Models\ClinicalCase;
use App\Models\ClinicalProgressNote;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Carbon\Carbon;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncTest extends TestCase
{
    private string $token;

    private Tenant $tenant;

    private User $veterinarian;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->seed(TenantSeeder::class);
        $this->seed(TenantStaffSeeder::class);

        // Authenticate as Dr. John Miller on AlphaPet
        $loginResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/login', [
                'email' => 'john.miller@alphavet.com',
                'password' => 'password123',
            ]);

        $this->token = $loginResponse->json('token');
        $this->tenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);
        $this->veterinarian = User::query()->where('email', 'john.miller@alphavet.com')->firstOrFail();
        $tenantManager->reset();
    }

    public function test_pull_returns_deltas_with_pagination_and_next_since(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        Client::factory()->count(3)->create();

        $tenantManager->reset();

        // 1. Pull with limit 2 to trigger has_more = true
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/sync/pull?limit=2');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'timestamp',
                'has_more',
                'next_since',
                'deltas' => [
                    'clients',
                    'patients',
                    'cases',
                    'progress_notes',
                    'inventory_products',
                    'inventory_batches',
                    'inventory_containers',
                ],
            ]);

        $this->assertTrue($response->json('has_more'));
        $this->assertNotNull($response->json('next_since'));

        // 2. Pull with large limit to ensure has_more = false
        $responseLarge = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/sync/pull?limit=10000');

        $responseLarge->assertStatus(200);
        $this->assertFalse($responseLarge->json('has_more'));
    }

    public function test_pull_maps_clinical_case_id_to_case_id_for_progress_notes(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = Client::factory()->create();
        $patient = Patient::factory()->create(['client_id' => $client->id]);
        $case = ClinicalCase::factory()->create([
            'patient_id' => $patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
        ]);

        $note = ClinicalProgressNote::factory()->create([
            'clinical_case_id' => $case->id,
            'vet_id' => $this->veterinarian->id,
        ]);

        $tenantManager->reset();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/sync/pull');

        $response->assertStatus(200);
        $notes = collect($response->json('deltas.progress_notes'));
        $matched = $notes->firstWhere('id', $note->id);

        $this->assertNotNull($matched);
        $this->assertEquals($case->id, $matched['case_id']);
        $this->assertEquals($case->id, $matched['clinical_case_id']);
    }

    public function test_push_returns_202_accepted_and_dispatches_job(): void
    {
        Queue::fake([ProcessSyncMutationsJob::class]);

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', [
                'mutations' => [
                    [
                        'mutation_id' => 'mut-async-01',
                        'entity_type' => 'client',
                        'entity_id' => (string) Str::uuid(),
                        'action' => 'create',
                        'payload' => ['name' => 'Async Queued Client'],
                    ],
                ],
            ]);

        $response->assertStatus(202)
            ->assertJsonPath('status', 'accepted')
            ->assertJsonStructure(['status', 'batch_id', 'message']);

        $batchId = $response->json('batch_id');

        Queue::assertPushed(ProcessSyncMutationsJob::class, function (ProcessSyncMutationsJob $job) use ($batchId): bool {
            return $job->batchId === $batchId
                && count($job->mutations) === 1
                && $job->userId === $this->veterinarian->id;
        });
    }

    public function test_job_processes_mutations_and_broadcasts_completion_event(): void
    {
        Event::fake([SyncBatchCompletedEvent::class]);

        $clientId = (string) Str::uuid();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', [
                'mutations' => [
                    [
                        'mutation_id' => 'mut-event-01',
                        'entity_type' => 'client',
                        'entity_id' => $clientId,
                        'action' => 'create',
                        'payload' => ['name' => 'Event Broadcasted Client'],
                    ],
                ],
            ]);

        $response->assertStatus(202);
        $batchId = $response->json('batch_id');

        Event::assertDispatched(SyncBatchCompletedEvent::class, function (SyncBatchCompletedEvent $event) use ($batchId): bool {
            return $event->batchId === $batchId
                && count($event->results) === 1
                && $event->results[0]['status'] === 'applied';
        });
    }

    public function test_push_ingests_and_upserts_clients_preserving_created_at(): void
    {
        $clientId = (string) Str::uuid();
        $initialCreatedAt = Carbon::now()->subDays(5)->toIso8601String();

        // 1. Initial push (create)
        $createResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', [
                'mutations' => [
                    [
                        'mutation_id' => 'mut-client-01',
                        'entity_type' => 'client',
                        'entity_id' => $clientId,
                        'action' => 'create',
                        'payload' => [
                            'name' => 'Initial Client Name',
                            'phone_primary' => '+123456789',
                            'created_at' => $initialCreatedAt,
                        ],
                    ],
                ],
            ]);

        $createResponse->assertStatus(202);

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = DB::table('clients')->where('id', $clientId)->first();
        $this->assertNotNull($client);
        $this->assertEquals('Initial Client Name', $client->name);
        $savedCreatedAt = $client->created_at;

        $tenantManager->reset();

        // 2. Subsequent push (update with spoofed created_at)
        $updateResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', [
                'mutations' => [
                    [
                        'mutation_id' => 'mut-client-02',
                        'entity_type' => 'client',
                        'entity_id' => $clientId,
                        'action' => 'update',
                        'payload' => [
                            'name' => 'Updated Client Name',
                            'created_at' => Carbon::now()->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        $updateResponse->assertStatus(202);

        $tenantManager->switch($this->tenant);
        $updatedClient = DB::table('clients')->where('id', $clientId)->first();
        $this->assertEquals('Updated Client Name', $updatedClient->name);
        // Verify created_at was NOT overwritten
        $this->assertEquals($savedCreatedAt, $updatedClient->created_at);

        $tenantManager->reset();
    }

    public function test_push_ingests_patient_with_auto_provisioned_client_and_normalized_gender(): void
    {
        $missingClientId = (string) Str::uuid();
        $patientId = (string) Str::uuid();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', [
                'mutations' => [
                    [
                        'mutation_id' => 'mut-patient-01',
                        'entity_type' => 'patient',
                        'entity_id' => $patientId,
                        'action' => 'create',
                        'payload' => [
                            'client_id' => $missingClientId,
                            'name_or_tag' => 'Bella',
                            'species' => 'Canine',
                            'gender' => 'male_neutered',
                            'allergies' => ['amoxicillin', 'beef protein'],
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(202);

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        // Verify missing client was auto-provisioned
        $clientExists = DB::table('clients')->where('id', $missingClientId)->exists();
        $this->assertTrue($clientExists);

        // Verify patient was inserted with normalized gender and JSON allergies
        $patient = DB::table('patients')->where('id', $patientId)->first();
        $this->assertNotNull($patient);
        $this->assertEquals('Bella', $patient->name_or_tag);
        $this->assertEquals('neutered_male', $patient->gender);
        $this->assertStringContainsString('amoxicillin', (string) $patient->allergies);

        $tenantManager->reset();
    }

    public function test_push_auto_provisions_missing_patient_for_case_mutation(): void
    {
        $missingPatientId = (string) Str::uuid();
        $caseId = (string) Str::uuid();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', [
                'mutations' => [
                    [
                        'mutation_id' => 'mut-case-01',
                        'entity_type' => 'case',
                        'entity_id' => $caseId,
                        'action' => 'create',
                        'payload' => [
                            'patient_id' => $missingPatientId,
                            'primary_veterinarian_id' => $this->veterinarian->id,
                            'chief_complaint' => 'Acute lameness in left hind leg',
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(202);

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        // Verify patient was auto-provisioned to prevent foreign key errors
        $patientExists = DB::table('patients')->where('id', $missingPatientId)->exists();
        $this->assertTrue($patientExists);

        // Verify case was persisted successfully
        $case = DB::table('cases')->where('id', $caseId)->first();
        $this->assertNotNull($case);
        $this->assertEquals('Acute lameness in left hind leg', $case->chief_complaint);
        $this->assertEquals($missingPatientId, $case->patient_id);

        $tenantManager->reset();
    }

    public function test_push_ingests_progress_note_with_case_id_mapping_and_weight_sync(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = Client::factory()->create();
        $patient = Patient::factory()->create([
            'client_id' => $client->id,
            'current_weight_kg' => '20.00',
        ]);
        $case = ClinicalCase::factory()->create([
            'patient_id' => $patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
        ]);

        $tenantManager->reset();

        $noteId = (string) Str::uuid();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', [
                'mutations' => [
                    [
                        'mutation_id' => 'mut-note-01',
                        'entity_type' => 'clinical_progress_note',
                        'entity_id' => $noteId,
                        'action' => 'create',
                        'payload' => [
                            'case_id' => $case->id,
                            'veterinarian_id' => $this->veterinarian->id,
                            'temperature_c' => 38.8,
                            'heart_rate_bpm' => 95,
                            'weight_kg' => 24.50,
                            'subjective_notes' => 'Patient alert and responsive.',
                            'is_signed' => true,
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(202);

        $tenantManager->switch($this->tenant);

        $note = DB::table('clinical_progress_notes')->where('id', $noteId)->first();
        $this->assertNotNull($note);
        $this->assertEquals($case->id, $note->clinical_case_id);
        $this->assertEquals(95, $note->heart_rate_bpm);
        $this->assertEquals(1, $note->is_signed);

        // Verify patient weight was updated to 24.50
        $updatedPatient = DB::table('patients')->where('id', $patient->id)->first();
        $this->assertEquals('24.500', $updatedPatient->current_weight_kg);

        $tenantManager->reset();
    }

    public function test_push_handles_unknown_entity_type_as_ignored(): void
    {
        Event::fake([SyncBatchCompletedEvent::class]);

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', [
                'mutations' => [
                    [
                        'mutation_id' => 'mut-unknown-01',
                        'entity_type' => 'telemetry_event',
                        'entity_id' => (string) Str::uuid(),
                        'action' => 'create',
                        'payload' => ['foo' => 'bar'],
                    ],
                ],
            ]);

        $response->assertStatus(202);

        Event::assertDispatched(SyncBatchCompletedEvent::class, function (SyncBatchCompletedEvent $event): bool {
            return count($event->results) === 1
                && $event->results[0]['status'] === 'ignored';
        });
    }

    public function test_job_isolates_failures_without_breaking_other_mutations(): void
    {
        Event::fake([SyncBatchCompletedEvent::class]);

        $validClientId = (string) Str::uuid();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', [
                'mutations' => [
                    // Mutation 1: Valid client
                    [
                        'mutation_id' => 'mut-valid-01',
                        'entity_type' => 'client',
                        'entity_id' => $validClientId,
                        'action' => 'create',
                        'payload' => [
                            'name' => 'Valid Ingested Client',
                            'phone_primary' => '+1999888777',
                        ],
                    ],
                    // Mutation 2: Missing entity ID (should fail)
                    [
                        'mutation_id' => 'mut-invalid-02',
                        'entity_type' => 'client',
                        'entity_id' => '',
                        'action' => 'create',
                        'payload' => [],
                    ],
                ],
            ]);

        $response->assertStatus(202);

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        // Verify the valid mutation was committed despite the second failing
        $clientExists = DB::table('clients')->where('id', $validClientId)->exists();
        $this->assertTrue($clientExists);

        $tenantManager->reset();

        Event::assertDispatched(SyncBatchCompletedEvent::class, function (SyncBatchCompletedEvent $event): bool {
            return count($event->results) === 2
                && $event->results[0]['status'] === 'applied'
                && $event->results[1]['status'] === 'failed';
        });
    }

    public function test_push_rejects_mutation_on_signed_locked_visit(): void
    {
        Event::fake([SyncBatchCompletedEvent::class]);

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = Client::factory()->create();
        $patient = Patient::factory()->create(['client_id' => $client->id]);
        $case = ClinicalCase::factory()->create([
            'patient_id' => $patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
        ]);

        $visitId = (string) Str::uuid();
        DB::table('visits')->insert([
            'id' => $visitId,
            'case_id' => $case->id,
            'title' => 'Signed Visit Encounter',
            'visit_type' => 'outpatient',
            'status' => 'completed',
            'is_signed' => 1,
            'signed_at' => now(),
            'signed_by' => $this->veterinarian->name,
            'visit_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenantManager->reset();

        // Attempt to update the signed visit via offline sync push
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', [
                'mutations' => [
                    [
                        'mutation_id' => 'mut-signed-visit-01',
                        'entity_type' => 'visit',
                        'entity_id' => $visitId,
                        'action' => 'update',
                        'payload' => [
                            'title' => 'Tampered Title Attempt',
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(202);

        Event::assertDispatched(SyncBatchCompletedEvent::class, function (SyncBatchCompletedEvent $event): bool {
            return count($event->results) === 1
                && $event->results[0]['status'] === 'rejected'
                && str_contains($event->results[0]['message'], 'is signed and locked');
        });
    }

    public function test_push_rejects_note_mutation_on_signed_locked_visit(): void
    {
        Event::fake([SyncBatchCompletedEvent::class]);

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = Client::factory()->create();
        $patient = Patient::factory()->create(['client_id' => $client->id]);
        $case = ClinicalCase::factory()->create([
            'patient_id' => $patient->id,
            'primary_veterinarian_id' => $this->veterinarian->id,
        ]);

        $visitId = (string) Str::uuid();
        DB::table('visits')->insert([
            'id' => $visitId,
            'case_id' => $case->id,
            'title' => 'Signed Visit Encounter',
            'visit_type' => 'outpatient',
            'status' => 'completed',
            'is_signed' => 1,
            'signed_at' => now(),
            'signed_by' => $this->veterinarian->name,
            'visit_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenantManager->reset();

        $noteId = (string) Str::uuid();

        // Attempt to add a note attached to this locked visit
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', [
                'mutations' => [
                    [
                        'mutation_id' => 'mut-signed-note-01',
                        'entity_type' => 'clinical_progress_note',
                        'entity_id' => $noteId,
                        'action' => 'create',
                        'payload' => [
                            'case_id' => $case->id,
                            'visit_id' => $visitId,
                            'subjective_notes' => 'Attempting post-lock edit',
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(202);

        Event::assertDispatched(SyncBatchCompletedEvent::class, function (SyncBatchCompletedEvent $event): bool {
            return count($event->results) === 1
                && $event->results[0]['status'] === 'rejected'
                && str_contains($event->results[0]['message'], 'is signed and locked');
        });
    }

    public function test_push_ignores_mutation_older_than_server_record_under_lww(): void
    {
        Event::fake([SyncBatchCompletedEvent::class]);

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $client = Client::factory()->create([
            'name' => 'Server Authoritative Name',
            'updated_at' => Carbon::now()->subMinutes(5),
        ]);

        $tenantManager->reset();

        // Mutation with older updated_at (15 mins ago)
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', [
                'mutations' => [
                    [
                        'mutation_id' => 'mut-lww-01',
                        'entity_type' => 'client',
                        'entity_id' => $client->id,
                        'action' => 'update',
                        'payload' => [
                            'name' => 'Stale Offline Name',
                            'updated_at' => Carbon::now()->subMinutes(15)->toIso8601String(),
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(202);

        Event::assertDispatched(SyncBatchCompletedEvent::class, function (SyncBatchCompletedEvent $event): bool {
            return count($event->results) === 1
                && $event->results[0]['status'] === 'ignored'
                && str_contains($event->results[0]['message'], 'LWW');
        });

        // Ensure server record was NOT overwritten
        $tenantManager->switch($this->tenant);
        $this->assertEquals('Server Authoritative Name', DB::table('clients')->where('id', $client->id)->value('name'));
        $tenantManager->reset();
    }

    public function test_push_deduplicates_mutation_with_persistent_idempotency_key(): void
    {
        Event::fake([SyncBatchCompletedEvent::class]);

        $clientId = (string) Str::uuid();
        $idempotencyKey = 'idem-unique-key-999';

        $mutationPayload = [
            'mutations' => [
                [
                    'mutation_id' => 'mut-idem-01',
                    'idempotency_key' => $idempotencyKey,
                    'entity_type' => 'client',
                    'entity_id' => $clientId,
                    'action' => 'create',
                    'payload' => [
                        'name' => 'Idempotent Client',
                    ],
                ],
            ],
        ];

        // 1. First execution
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', $mutationPayload)
            ->assertStatus(202);

        Event::assertDispatched(SyncBatchCompletedEvent::class, function (SyncBatchCompletedEvent $event): bool {
            return count($event->results) === 1
                && $event->results[0]['status'] === 'applied'
                && ! isset($event->results[0]['message']);
        });

        Event::fake([SyncBatchCompletedEvent::class]);

        // 2. Replay with identical idempotency_key
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', $mutationPayload)
            ->assertStatus(202);

        Event::assertDispatched(SyncBatchCompletedEvent::class, function (SyncBatchCompletedEvent $event): bool {
            return count($event->results) === 1
                && $event->results[0]['status'] === 'applied'
                && str_contains($event->results[0]['message'] ?? '', 'Idempotent replay');
        });
    }
}
