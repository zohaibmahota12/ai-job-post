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
     * Numeric scoring is provided by MatchScorer. Criterion outcomes remain available here.
     */
    public function score(): null
    {
        return null;
    }

    public function resultFor(string $key): ?CriterionResult
    {
        foreach ($this->results as $result) {
            if ($result->key === $key) {
                return $result;
            }
        }

        return null;
    }

    public function outcomeFor(string $key): CriterionOutcome
    {
        return $this->resultFor($key)?->outcome ?? CriterionOutcome::Unknown;
    }
}
