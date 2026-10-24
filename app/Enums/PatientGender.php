<?php

declare(strict_types=1);

namespace App\Enums;

enum PatientGender: string
{
    case IntactMale = 'intact_male';
    case NeuteredMale = 'neutered_male';
    case IntactFemale = 'intact_female';
    case SpayedFemale = 'spayed_female';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::IntactMale => 'Intact Male',
            self::NeuteredMale => 'Neutered / Castrated Male',
            self::IntactFemale => 'Intact Female',
            self::SpayedFemale => 'Spayed Female',
            self::Unknown => 'Unknown Gender',
        };
    }

    public function isNeutered(): bool
    {
        return in_array($this, [self::NeuteredMale, self::SpayedFemale], true);
    }
}
