<?php

namespace App;

enum RemotePreference: string
{
    case Remote = 'remote';
    case Hybrid = 'hybrid';
    case Onsite = 'onsite';
    case Any = 'any';

    public function label(): string
    {
        return match ($this) {
            self::Remote => 'Remote',
            self::Hybrid => 'Hybrid',
            self::Onsite => 'On-site',
            self::Any => 'Any',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
