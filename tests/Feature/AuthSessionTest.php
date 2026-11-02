<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantStaffSeeder;
use Tests\TestCase;

class AuthSessionTest extends TestCase
{
    private string $token;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_authenticated_staff_can_fetch_own_profile_and_capabilities(): void
    {
        $response = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'john.miller@alphavet.com')
            ->assertJsonPath('user.name', 'Dr. John Miller')
            ->assertJsonPath('user.role', 'owner')
            ->assertJsonPath('tenant.slug', 'alphavet')
            ->assertJsonPath('tenant.name', $this->tenant->name)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'profile' => [
                        'job_title',
                        'specialty',
                        'license_number',
                    ],
                ],
                'tenant' => [
                    'id',
                    'name',
                    'slug',
                ],
                'capabilities' => [
                    'can_manage_staff',
                    'can_witness_controlled_substances',
                    'can_access_owner_dashboard',
                ],
            ]);

        $this->assertTrue($response->json('capabilities.can_manage_staff'));
        $this->assertTrue($response->json('capabilities.can_access_owner_dashboard'));
    }

    public function test_staff_can_logout_and_revoke_current_token(): void
    {
        // 1. Initial request with token succeeds
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200);

        // 2. Call logout endpoint
        $logoutResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully.']);

        // Forget cached auth guards in test runner to simulate a fresh incoming request
        auth()->forgetGuards();

        // 3. Subsequent request with revoked token fails with 401
        $subsequentResponse = $this->withHeader('X-Tenant-ID', 'alphavet')
            ->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/auth/me');

        $subsequentResponse->assertStatus(401);
    }

    public function test_unauthenticated_request_to_me_and_logout_returns_401(): void
    {
        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);

        $this->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(401);
    }
}
