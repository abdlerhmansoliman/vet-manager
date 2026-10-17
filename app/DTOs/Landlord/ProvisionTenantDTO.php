<?php

declare(strict_types=1);

namespace App\DTOs\Landlord;

use App\Enums\TenantPlan;

readonly class ProvisionTenantDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public TenantPlan $plan,
        public int $storageLimitBytes,
        public string $ownerName,
        public string $ownerEmail,
        public string $ownerPassword,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $plan = $data['plan'] instanceof TenantPlan
            ? $data['plan']
            : TenantPlan::from((string) $data['plan']);

        $storageLimitBytes = isset($data['storage_limit_gb']) && (int) $data['storage_limit_gb'] > 0
            ? (int) $data['storage_limit_gb'] * 1024 * 1024 * 1024
            : $plan->defaultStorageBytes();

        return new self(
            name: trim((string) $data['name']),
            slug: strtolower(trim((string) $data['slug'])),
            plan: $plan,
            storageLimitBytes: $storageLimitBytes,
            ownerName: trim((string) $data['owner_name']),
            ownerEmail: strtolower(trim((string) $data['owner_email'])),
            ownerPassword: (string) $data['owner_password'],
        );
    }
}
