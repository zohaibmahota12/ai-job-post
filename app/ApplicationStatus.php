<?php

namespace App;

enum ApplicationStatus: string
{
    case New = 'NEW';
    case Saved = 'SAVED';
    case ProposalDraft = 'PROPOSAL_DRAFT';
    case Applied = 'APPLIED';
    case Interview = 'INTERVIEW';
    case Hired = 'HIRED';
    case Rejected = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Saved => 'Saved',
            self::ProposalDraft => 'Proposal draft',
            self::Applied => 'Applied',
            self::Interview => 'Interview',
            self::Hired => 'Hired',
            self::Rejected => 'Rejected',
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
