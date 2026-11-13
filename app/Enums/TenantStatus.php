<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantStatus: string
{
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Failed = 'failed';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isSuspended(): bool
    {
        return $this === self::Suspended;
    }

    public function isProvisioning(): bool
    {
        return $this === self::Provisioning;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }

    public function label(): string
    {
        return match ($this) {
            self::Provisioning => 'Provisioning',
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Suspended => 'Suspended',
            self::Failed => 'Failed',
        };
    }
}
