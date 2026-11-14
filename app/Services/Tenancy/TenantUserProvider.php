<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Throwable;

class TenantUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by their unique identifier.
     *
     * Safely prevents querying the tenant connection when no tenant is resolved
     * or selected, preventing SQLSTATE 1046 "No database selected" errors.
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        if (! $this->ensureTenantContext()) {
            return null;
        }

        try {
            return parent::retrieveById($identifier);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        if (! $this->ensureTenantContext()) {
            return null;
        }

        try {
            return parent::retrieveByToken($identifier, $token);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Retrieve a user by the given credentials.
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (! $this->ensureTenantContext()) {
            return null;
        }

        try {
            return parent::retrieveByCredentials($credentials);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Ensure a tenant is active and resolved before querying tenant users.
     */
    private function ensureTenantContext(): bool
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        if ($tenantManager->isResolved()) {
            return true;
        }

        if (function_exists('session') && session()->isStarted() && session()->has('active_tenant_id')) {
            $tenantId = (string) session('active_tenant_id');
            if ($tenantId !== '') {
                $tenant = $tenantManager->resolve($tenantId);
                if ($tenant instanceof Tenant && $tenant->isActive() && ! $tenant->isSuspended()) {
                    $tenantManager->switch($tenant);

                    return true;
                }
            }
        }

        return false;
    }
}
