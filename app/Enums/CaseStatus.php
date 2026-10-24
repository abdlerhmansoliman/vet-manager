<?php

declare(strict_types=1);

namespace App\Enums;

enum CaseStatus: string
{
    case Open = 'open';
    case UnderObservation = 'under_observation';
    case CriticalCare = 'critical_care';
    case Discharged = 'discharged';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open / Active Workup',
            self::UnderObservation => 'Under Clinical Observation',
            self::CriticalCare => 'ICU / Critical Care',
            self::Discharged => 'Discharged',
            self::Closed => 'Closed & Archived',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::UnderObservation, self::CriticalCare], true);
    }
}
