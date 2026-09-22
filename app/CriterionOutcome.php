<?php

namespace App;

enum CriterionOutcome: string
{
    case Pass = 'pass';
    case Fail = 'fail';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Pass => 'Matches',
            self::Fail => 'Does not match',
            self::Unknown => 'Not enough information',
        };
    }
}
