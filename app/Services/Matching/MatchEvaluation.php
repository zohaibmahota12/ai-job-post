<?php

namespace App\Services\Matching;

use App\CriterionOutcome;

final class MatchEvaluation
{
    /**
     * @param  list<CriterionResult>  $results
     */
    public function __construct(public array $results) {}

    /**
     * Numeric scoring is a later phase. This foundation reports criterion outcomes only.
     */
    public function score(): null
    {
        return null;
    }

    public function outcomeFor(string $key): CriterionOutcome
    {
        foreach ($this->results as $result) {
            if ($result->key === $key) {
                return $result->outcome;
            }
        }

        return CriterionOutcome::Unknown;
    }
}
