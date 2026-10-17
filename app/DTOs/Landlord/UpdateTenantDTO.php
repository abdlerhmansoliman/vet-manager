<?php

declare(strict_types=1);

namespace App\DTOs\Landlord;

use App\Enums\TenantPlan;

readonly class UpdateTenantDTO
{
    public function __construct(
        public string $name,
        public TenantPlan $plan,
        public int $storageLimitBytes,
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
            plan: $plan,
            storageLimitBytes: $storageLimitBytes,
        );
    }
}
