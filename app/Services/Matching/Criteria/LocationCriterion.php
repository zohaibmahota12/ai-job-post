<?php

namespace App\Services\Matching\Criteria;

use App\CriterionOutcome;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Matching\CriterionResult;
use App\Services\Matching\MatchCriterion;
use App\Workplace;
use Illuminate\Support\Str;

class LocationCriterion implements MatchCriterion
{
    public function evaluate(User $user, Opportunity $opportunity): CriterionResult
    {
        if ($opportunity->workplace === Workplace::Remote) {
            return $this->result(CriterionOutcome::Unknown, 'Remote listings are not filtered by city.');
        }

        $preferred = trim((string) $user->profile?->location);
        $actual = trim((string) $opportunity->location);

        if ($preferred === '' || $actual === '') {
            return $this->result(CriterionOutcome::Unknown, 'A location is missing on the profile or the listing.');
        }

        $preferredLower = Str::lower($preferred);
        $actualLower = Str::lower($actual);

        if (Str::contains($actualLower, $preferredLower) || Str::contains($preferredLower, $actualLower)) {
            return $this->result(CriterionOutcome::Pass, 'Location lines up with '.$preferred.'.');
        }

        return $this->result(CriterionOutcome::Fail, 'Prefers '.$preferred.', listing says '.$actual.'.');
    }

    private function result(CriterionOutcome $outcome, string $detail): CriterionResult
    {
        return new CriterionResult('location', 'Location', $outcome, $detail);
    }
}
