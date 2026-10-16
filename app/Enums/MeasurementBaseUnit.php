<?php

declare(strict_types=1);

namespace App\Enums;

enum MeasurementBaseUnit: string
{
    case Ml = 'ml';
    case Mg = 'mg';
    case Tablet = 'tablet';
    case Drop = 'drop';
    case Piece = 'piece';
    case Gram = 'g';
}
