<?php

namespace App;

enum ProposalVersionSource: string
{
    case Ai = 'ai';
    case User = 'user';
    case Regenerate = 'regenerate';

    public function label(): string
    {
        return match ($this) {
            self::Ai => 'AI generated',
            self::User => 'User edit',
            self::Regenerate => 'AI regenerated',
        };
    }
}
