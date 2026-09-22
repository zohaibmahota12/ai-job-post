<?php

namespace App\Services\Matching\Criteria;

use App\CriterionOutcome;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Matching\CriterionResult;
use App\Services\Matching\MatchCriterion;

class ExperienceCriterion implements MatchCriterion
{
    public function evaluate(User $user, Opportunity $opportunity): CriterionResult
    {
        $years = $user->profile?->years_of_experience;

        if ($years === null) {
            return $this->result(CriterionOutcome::Unknown, 'Years of experience are not set on the profile.');
        }

        $required = $opportunity->required_experience_years;

        if ($required === null) {
            return $this->result(CriterionOutcome::Unknown, 'The listing does not state required experience.');
        }

        if ($years >= $required) {
            return $this->result(CriterionOutcome::Pass, $years.' years meets the '.$required.' year requirement.');
        }

        return $this->result(CriterionOutcome::Fail, $years.' years is below the '.$required.' year requirement.');
    }

    private function result(CriterionOutcome $outcome, string $detail): CriterionResult
    {
        return new CriterionResult('experience', 'Experience', $outcome, $detail);
    }
}
