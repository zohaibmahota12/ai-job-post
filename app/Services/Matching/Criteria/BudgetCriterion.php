<?php

namespace App\Services\Matching\Criteria;

use App\CriterionOutcome;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Matching\CriterionResult;
use App\Services\Matching\MatchCriterion;
use Illuminate\Support\Str;

class BudgetCriterion implements MatchCriterion
{
    public function evaluate(User $user, Opportunity $opportunity): CriterionResult
    {
        $minimum = $user->profile?->minimum_budget;

        if ($minimum === null || $minimum === '') {
            return $this->result(CriterionOutcome::Unknown, 'No minimum budget is set.');
        }

        $listingAmount = $opportunity->budget_max ?? $opportunity->budget_min;

        if ($listingAmount === null || $listingAmount === '') {
            return $this->result(CriterionOutcome::Unknown, 'The listing has no budget.');
        }

        $preferredCurrency = $this->currency($user->profile?->preferred_currency);
        $listingCurrency = $this->currency($opportunity->currency);

        if ($preferredCurrency === null || $listingCurrency === null || $preferredCurrency !== $listingCurrency) {
            return $this->result(CriterionOutcome::Unknown, 'Currencies differ or are missing, so budget was not compared.');
        }

        if (bccomp((string) $listingAmount, (string) $minimum, 2) >= 0) {
            return $this->result(CriterionOutcome::Pass, 'Budget meets the '.$minimum.' '.$preferredCurrency.' minimum.');
        }

        return $this->result(CriterionOutcome::Fail, 'Budget is below the '.$minimum.' '.$preferredCurrency.' minimum.');
    }

    private function currency(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return Str::upper(trim($value));
    }

    private function result(CriterionOutcome $outcome, string $detail): CriterionResult
    {
        return new CriterionResult('budget', 'Budget', $outcome, $detail);
    }
}
