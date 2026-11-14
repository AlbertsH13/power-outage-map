<?php

namespace App\Enums;

enum OutageStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Closed => 'Closed',
            self::Expired => 'Expired',
        };
    }
}
