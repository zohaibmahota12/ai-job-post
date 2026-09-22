<?php

namespace App\Services\Matching\Criteria;

use App\CriterionOutcome;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Matching\CriterionResult;
use App\Services\Matching\MatchCriterion;

class SkillOverlapCriterion implements MatchCriterion
{
    public function evaluate(User $user, Opportunity $opportunity): CriterionResult
    {
        $userSlugs = $user->skills->pluck('slug');
        $opportunitySlugs = $opportunity->skills->pluck('slug');

        if ($userSlugs->isEmpty() || $opportunitySlugs->isEmpty()) {
            return $this->result(CriterionOutcome::Unknown, 'Add skills on both sides before this can be compared.');
        }

        $overlap = $userSlugs->intersect($opportunitySlugs)->values();

        if ($overlap->isEmpty()) {
            return $this->result(CriterionOutcome::Fail, 'No skills in common.');
        }

        return $this->result(CriterionOutcome::Pass, 'Shared skills: '.$overlap->implode(', ').'.');
    }

    private function result(CriterionOutcome $outcome, string $detail): CriterionResult
    {
        return new CriterionResult('skills', 'Skills', $outcome, $detail);
    }
}
