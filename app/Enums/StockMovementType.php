<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case Receive = 'receive';
    case Dispense = 'dispense';
    case DoseAdministration = 'dose_administration';
    case WastageDiscard = 'wastage_discard';
    case AuditAdjustment = 'audit_adjustment';
    case Transfer = 'transfer';

    public function isDeduction(): bool
    {
        return in_array($this, [self::Dispense, self::DoseAdministration, self::WastageDiscard], true);
    }
}
