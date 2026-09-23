<?php

namespace App;

enum SourceHealth: string
{
    case Healthy = 'healthy';
    case Warning = 'warning';
    case Failing = 'failing';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Healthy => 'Healthy',
            self::Warning => 'Warning',
            self::Failing => 'Failing',
            self::Disabled => 'Disabled',
        };
    }
}
