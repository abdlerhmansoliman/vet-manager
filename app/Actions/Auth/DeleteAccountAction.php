<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Tenancy\TenantStaffSyncService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeleteAccountAction
{
    public function __construct(
        private readonly TenantStaffSyncService $tenantStaffSyncService
    ) {}

    /**
     * Delete or deactivate the authenticated user account and revoke all access tokens.
     *
     * @throws ValidationException
     */
    public function execute(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password was incorrect.'],
            ]);
        }

        // Revoke all active Sanctum tokens immediately
        $user->tokens()->delete();

        // Attempt permanent removal; if restricted by historical clinical foreign keys,
        // sanitize credentials and mark deactivated while preserving statutory audit records.
        try {
            $user->delete();
        } catch (QueryException) {
            $user->update([
                'is_active' => false,
                'password' => Hash::make(Str::random(40)),
                'pin' => null,
            ]);
            $user->profile?->update(['is_available' => false]);
            $this->tenantStaffSyncService->deleteUser($user);
        }
    }
}
