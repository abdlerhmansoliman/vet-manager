<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantPlan: string
{
    case Solo = 'solo';
    case Clinic = 'clinic';
    case Hospital = 'hospital';
    case Enterprise = 'enterprise';

    /**
     * Base storage allowance in bytes.
     */
    public function defaultStorageBytes(): int
    {
        return match ($this) {
            self::Solo => 5 * 1024 * 1024 * 1024,         // 5 GB
            self::Clinic => 20 * 1024 * 1024 * 1024,      // 20 GB
            self::Hospital => 100 * 1024 * 1024 * 1024,   // 100 GB
            self::Enterprise => 500 * 1024 * 1024 * 1024, // 500 GB
        };
    }
}
