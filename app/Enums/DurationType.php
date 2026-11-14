<?php

namespace App\Enums;

enum DurationType: string
{
    case Fixed = 'fixed';
    case UntilClosed = 'until_closed';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed Duration',
            self::UntilClosed => 'Until I close it',
        };
    }
}
