<?php

namespace App;

enum OpportunityStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Closed => 'Closed',
            self::Expired => 'Expired',
        };
    }
}
