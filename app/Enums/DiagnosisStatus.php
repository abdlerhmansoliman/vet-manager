<?php

declare(strict_types=1);

namespace App\Enums;

enum DiagnosisStatus: string
{
    case Active = 'active';
    case Resolved = 'resolved';
    case Chronic = 'chronic';
    case Tentative = 'tentative';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active Diagnosis',
            self::Resolved => 'Resolved / Cured',
            self::Chronic => 'Chronic Condition',
            self::Tentative => 'Tentative / Differential',
        };
    }
}
