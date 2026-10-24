<?php

declare(strict_types=1);

namespace App\Enums;

enum CasePriority: string
{
    case Routine = 'routine';
    case Urgent = 'urgent';
    case EmergencyCritical = 'emergency_critical';

    public function label(): string
    {
        return match ($this) {
            self::Routine => 'Routine Consultation',
            self::Urgent => 'Urgent Priority',
            self::EmergencyCritical => 'Life-Threatening Emergency / Critical',
        };
    }
}
