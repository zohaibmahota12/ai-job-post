<?php

namespace App\Services\Matching\Criteria;

use App\CriterionOutcome;
use App\JobType;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Matching\CriterionResult;
use App\Services\Matching\MatchCriterion;

class JobTypeCriterion implements MatchCriterion
{
    public function evaluate(User $user, Opportunity $opportunity): CriterionResult
    {
        $preferred = $user->profile?->preferred_job_type;

        if ($preferred === null || $preferred === JobType::Any) {
            return $this->result(CriterionOutcome::Unknown, 'No job type preference is set.');
        }

        if ($opportunity->job_type === null) {
            return $this->result(CriterionOutcome::Unknown, 'The listing does not name a job type.');
        }

        if ($preferred === $opportunity->job_type) {
            return $this->result(CriterionOutcome::Pass, 'Job type matches '.$preferred->label().'.');
        }

        return $this->result(
            CriterionOutcome::Fail,
            'Prefers '.$preferred->label().', listing is '.$opportunity->job_type->label().'.',
        );
    }

    private function result(CriterionOutcome $outcome, string $detail): CriterionResult
    {
        return new CriterionResult('job_type', 'Job type', $outcome, $detail);
    }
}
