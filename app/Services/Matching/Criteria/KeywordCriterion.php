<?php

namespace App\Services\Matching\Criteria;

use App\CriterionOutcome;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Matching\CriterionResult;
use App\Services\Matching\MatchCriterion;
use Illuminate\Support\Str;

class KeywordCriterion implements MatchCriterion
{
    public function evaluate(User $user, Opportunity $opportunity): CriterionResult
    {
        $profile = $user->profile;
        $keywords = $profile?->keywords ?? [];
        $excluded = $profile?->excluded_keywords ?? [];

        if ($keywords === [] && $excluded === []) {
            return $this->result(CriterionOutcome::Unknown, 'No keywords are set on the profile.');
        }

        $haystack = Str::lower(trim($opportunity->title.' '.$opportunity->description));

        foreach ($excluded as $keyword) {
            if ($this->contains($haystack, $keyword)) {
                return $this->result(CriterionOutcome::Fail, 'Excluded keyword found: '.$keyword.'.');
            }
        }

        if ($keywords === []) {
            return $this->result(CriterionOutcome::Unknown, 'No preferred keywords are set.');
        }

        $matched = [];

        foreach ($keywords as $keyword) {
            if ($this->contains($haystack, $keyword)) {
                $matched[] = $keyword;
            }
        }

        if ($matched === []) {
            return $this->result(CriterionOutcome::Fail, 'None of the preferred keywords appear in the listing.');
        }

        return $this->result(CriterionOutcome::Pass, 'Keywords found: '.implode(', ', $matched).'.');
    }

    private function contains(string $haystack, mixed $keyword): bool
    {
        if (! is_string($keyword) || trim($keyword) === '') {
            return false;
        }

        return Str::contains($haystack, Str::lower(trim($keyword)));
    }

    private function result(CriterionOutcome $outcome, string $detail): CriterionResult
    {
        return new CriterionResult('keywords', 'Keywords', $outcome, $detail);
    }
}
