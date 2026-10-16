<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginDTO;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginAction
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * @return array{token: string, user: User, tenant: Tenant}
     *
     * @throws ValidationException
     */
    public function execute(LoginDTO $dto): array
    {
        $tenant = $this->tenantManager->current();

        if (! $tenant instanceof Tenant) {
            throw ValidationException::withMessages([
                'tenant' => ['No active tenant context resolved for login.'],
            ]);
        }

        $user = User::query()
            ->with('profile')
            ->where('email', $dto->email)
            ->first();

        if (! $user instanceof User || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account is deactivated. Please contact your clinic manager.'],
            ]);
        }

        // Mint tenant-scoped token with role abilities
        $abilities = [
            "tenant:{$tenant->id}",
            "role:{$user->role->value}",
        ];

        $tokenName = $dto->deviceName ?? 'mobile_client';
        $token = $user->createToken($tokenName, $abilities)->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
            'tenant' => $tenant,
        ];
    }
}
