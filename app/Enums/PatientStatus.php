<?php

declare(strict_types=1);

namespace App\Enums;

enum PatientStatus: string
{
    case Active = 'active';
    case Deceased = 'deceased';
    case Transferred = 'transferred';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active Patient',
            self::Deceased => 'Deceased',
            self::Transferred => 'Transferred Practice',
            self::Archived => 'Archived Record',
        };
    }
}
