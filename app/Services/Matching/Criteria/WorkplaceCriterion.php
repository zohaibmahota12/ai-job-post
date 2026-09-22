<?php

namespace App\Services\Matching\Criteria;

use App\CriterionOutcome;
use App\Models\Opportunity;
use App\Models\User;
use App\RemotePreference;
use App\Services\Matching\CriterionResult;
use App\Services\Matching\MatchCriterion;
use App\Workplace;

class WorkplaceCriterion implements MatchCriterion
{
    public function evaluate(User $user, Opportunity $opportunity): CriterionResult
    {
        $preference = $user->profile?->remote_preference;

        if ($preference === null || $preference === RemotePreference::Any) {
            return $this->result(CriterionOutcome::Unknown, 'No workplace preference is set.');
        }

        $workplace = $opportunity->workplace;

        if ($workplace === null || $workplace === Workplace::Unspecified) {
            return $this->result(CriterionOutcome::Unknown, 'The listing does not say whether it is remote.');
        }

        $expected = match ($preference) {
            RemotePreference::Remote => Workplace::Remote,
            RemotePreference::Hybrid => Workplace::Hybrid,
            RemotePreference::Onsite => Workplace::Onsite,
            RemotePreference::Any => null,
        };

        if ($expected === $workplace) {
            return $this->result(CriterionOutcome::Pass, 'Workplace matches '.$preference->label().'.');
        }

        return $this->result(
            CriterionOutcome::Fail,
            'Prefers '.$preference->label().', listing is '.$workplace->label().'.',
        );
    }

    private function result(CriterionOutcome $outcome, string $detail): CriterionResult
    {
        return new CriterionResult('workplace', 'Workplace', $outcome, $detail);
    }
}
