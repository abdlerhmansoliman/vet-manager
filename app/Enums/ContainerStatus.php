<?php

declare(strict_types=1);

namespace App\Enums;

enum ContainerStatus: string
{
    case Sealed = 'sealed';
    case Open = 'open';
    case Depleted = 'depleted';

    public function isOpen(): bool
    {
        return $this === self::Open;
    }

    public function isSealed(): bool
    {
        return $this === self::Sealed;
    }

    public function isDepleted(): bool
    {
        return $this === self::Depleted;
    }
}
