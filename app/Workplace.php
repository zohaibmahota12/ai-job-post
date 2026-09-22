<?php

namespace App;

enum Workplace: string
{
    case Remote = 'remote';
    case Hybrid = 'hybrid';
    case Onsite = 'onsite';
    case Unspecified = 'unspecified';

    public function label(): string
    {
        return match ($this) {
            self::Remote => 'Remote',
            self::Hybrid => 'Hybrid',
            self::Onsite => 'On-site',
            self::Unspecified => 'Unspecified',
        };
    }
}
