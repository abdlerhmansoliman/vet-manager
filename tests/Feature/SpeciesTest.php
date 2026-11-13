<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessSyncMutationsJob;
use App\Models\Species;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SpeciesTest extends TestCase
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

        // Authenticate on alphavet tenant
        $loginResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/login', [
                'email' => 'john.miller@alphavet.com',
                'password' => 'password123',
            ]);

        $this->token = $loginResponse->json('token');
        $this->tenant = Tenant::query()->where('slug', 'alphavet')->firstOrFail();

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);
        Species::query()->where('is_system_default', false)->forceDelete();
        $this->veterinarian = User::query()->where('email', 'john.miller@alphavet.com')->firstOrFail();
        $tenantManager->reset();
    }

    protected function tearDown(): void
    {
        if (isset($this->tenant)) {
            $tenantManager = app(TenantManager::class);
            $tenantManager->switch($this->tenant);
            Species::query()->where('is_system_default', false)->forceDelete();
            $tenantManager->reset();
        }

        parent::tearDown();
    }

    public function test_get_species_returns_active_species(): void
    {
        $response = $this->withHeaders([
            'X-Tenant-ID' => 'alphavet',
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v1/species');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'code', 'icon', 'is_system_default', 'is_active'],
                ],
            ]);

        $codes = collect($response->json('data'))->pluck('code')->all();
        $this->assertContains('canine', $codes);
        $this->assertContains('feline', $codes);
        $this->assertContains('camel', $codes);
    }

    public function test_post_species_registers_custom_species(): void
    {
        $response = $this->withHeaders([
            'X-Tenant-ID' => 'alphavet',
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v1/species', [
            'name' => 'Falcon',
            'icon' => 'bird',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Falcon')
            ->assertJsonPath('data.code', 'falcon')
            ->assertJsonPath('data.is_system_default', false);

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $this->assertDatabaseHas('species', [
            'name' => 'Falcon',
            'code' => 'falcon',
            'is_system_default' => 0,
        ]);

        $tenantManager->reset();
    }

    public function test_species_included_in_sync_pull_deltas(): void
    {
        $response = $this->withHeaders([
            'X-Tenant-ID' => 'alphavet',
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v1/sync/pull');

        $response->assertOk()
            ->assertJsonStructure([
                'deltas' => [
                    'species',
                ],
            ]);

        $speciesDeltas = $response->json('deltas.species');
        $this->assertNotEmpty($speciesDeltas);
    }

    public function test_species_mutation_processed_via_sync_push(): void
    {
        $speciesId = (string) Str::uuid();

        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/sync/push', [
                'mutations' => [
                    [
                        'mutation_id' => (string) Str::uuid(),
                        'entity_type' => 'species',
                        'entity_id' => $speciesId,
                        'action' => 'upsert',
                        'payload' => [
                            'id' => $speciesId,
                            'name' => 'Arabian Oryx',
                            'code' => 'arabian_oryx',
                            'icon' => 'oryx',
                            'is_system_default' => false,
                            'is_active' => true,
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(202);

        $tenantManager = app(TenantManager::class);
        $tenantManager->switch($this->tenant);

        $species = DB::table('species')->where('id', $speciesId)->first();
        $this->assertNotNull($species);
        $this->assertEquals('Arabian Oryx', $species->name);
        $this->assertEquals('arabian_oryx', $species->code);

        $tenantManager->reset();
    }
}
