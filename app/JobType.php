<?php

namespace App;

enum JobType: string
{
    case Freelance = 'freelance';
    case Contract = 'contract';
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Any = 'any';

    public function label(): string
    {
        return match ($this) {
            self::Freelance => 'Freelance',
            self::Contract => 'Contract',
            self::FullTime => 'Full-time',
            self::PartTime => 'Part-time',
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
