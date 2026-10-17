<?php

declare(strict_types=1);

namespace App\Enums;

enum LandlordRole: string
{
    case SuperAdmin = 'super_admin';
    case BillingOps = 'billing_ops';
    case SupportEngineer = 'support_engineer';

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canManageTenants(): bool
    {
        return in_array($this, [self::SuperAdmin, self::SupportEngineer], true);
    }

    public function canManageBilling(): bool
    {
        return in_array($this, [self::SuperAdmin, self::BillingOps], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrator',
            self::BillingOps => 'Billing Operations',
            self::SupportEngineer => 'Support Engineer',
        };
    }
}
