<?php

declare(strict_types=1);

namespace App\Enums;

enum CaseType: string
{
    case Inpatient = 'inpatient';
    case Outpatient = 'outpatient';
    case FieldFarmVisit = 'field_farm_visit';

    public function label(): string
    {
        return match ($this) {
            self::Inpatient => 'Hospital Inpatient',
            self::Outpatient => 'Outpatient Consultation',
            self::FieldFarmVisit => 'Ambulatory Field / Farm Visit',
        };
    }
}
