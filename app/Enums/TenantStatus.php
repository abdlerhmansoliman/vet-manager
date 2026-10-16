<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isSuspended(): bool
    {
        return $this === self::Suspended;
    }
}
