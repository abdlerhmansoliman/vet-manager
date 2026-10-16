<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantManager
{
    private ?Tenant $currentTenant = null;

    /**
     * Resolve tenant by UUID or unique slug, using Redis caching.
     */
    public function resolve(string $identifier): ?Tenant
    {
        $cacheKey = "tenant:meta:{$identifier}";

        /** @var array<string, mixed>|null $attributes */
        $attributes = Cache::remember($cacheKey, 3600, function () use ($identifier): ?array {
            $tenant = Tenant::query()
                ->where('id', $identifier)
                ->orWhere('slug', $identifier)
                ->first();

            return $tenant?->getAttributes();
        });

        if ($attributes === null) {
            return null;
        }

        /** @var Tenant $tenant */
        $tenant = (new Tenant)->newFromBuilder($attributes);

        return $tenant;
    }

    /**
     * Switch application context and default connection to the specified tenant.
     */
    public function switch(Tenant $tenant): void
    {
        Config::set('database.connections.tenant.database', $tenant->database_name);

        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');

        $this->currentTenant = $tenant;
    }

    /**
     * Reset default database connection back to the central Landlord.
     */
    public function reset(): void
    {
        DB::purge('tenant');
        DB::setDefaultConnection('landlord');

        $this->currentTenant = null;
    }

    /**
     * Get the active tenant instance.
     */
    public function current(): ?Tenant
    {
        return $this->currentTenant;
    }

    /**
     * Check if a tenant is currently resolved in context.
     */
    public function isResolved(): bool
    {
        return $this->currentTenant !== null;
    }

    /**
     * Provision the isolated physical database schema for a new tenant.
     */
    public function createDatabase(Tenant $tenant): void
    {
        $databaseName = preg_replace('/[^a-zA-Z0-9_]/', '', $tenant->database_name);

        DB::connection('landlord')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    /**
     * Drop the isolated physical database schema for a tenant.
     */
    public function dropDatabase(Tenant $tenant): void
    {
        $databaseName = preg_replace('/[^a-zA-Z0-9_]/', '', $tenant->database_name);

        DB::connection('landlord')->statement(
            "DROP DATABASE IF EXISTS `{$databaseName}`"
        );
    }

    /**
     * Invalidate cached tenant metadata.
     */
    public function forgetCache(Tenant|string $identifier): void
    {
        if ($identifier instanceof Tenant) {
            Cache::forget("tenant:meta:{$identifier->id}");
            Cache::forget("tenant:meta:{$identifier->slug}");
        } else {
            Cache::forget("tenant:meta:{$identifier}");
        }
    }
}
