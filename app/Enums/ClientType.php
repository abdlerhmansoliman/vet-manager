<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientType: string
{
    case Individual = 'individual';
    case FarmManager = 'farm_manager';
    case LivestockEnterprise = 'livestock_enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Individual Pet Owner',
            self::FarmManager => 'Ambulatory Farm Manager',
            self::LivestockEnterprise => 'Livestock / Production Enterprise',
        };
    }
}
