<?php

declare(strict_types=1);

namespace App\Enums;

enum ControlledSubstanceSchedule: string
{
    case ScheduleII = 'schedule_2';
    case ScheduleIII = 'schedule_3';
    case ScheduleIV = 'schedule_4';
    case ScheduleV = 'schedule_5';
    case Uncontrolled = 'uncontrolled';

    public function isControlled(): bool
    {
        return $this !== self::Uncontrolled;
    }

    public function label(): string
    {
        return match ($this) {
            self::ScheduleII => 'Schedule II (High abuse potential, strict DEA witness required)',
            self::ScheduleIII => 'Schedule III',
            self::ScheduleIV => 'Schedule IV',
            self::ScheduleV => 'Schedule V',
            self::Uncontrolled => 'Uncontrolled / Standard Pharmaceutical',
        };
    }
}
