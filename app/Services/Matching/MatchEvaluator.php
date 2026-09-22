<?php

namespace App\Services\Matching;

use App\Models\Opportunity;
use App\Models\User;

class MatchEvaluator
{
    /**
     * @param  list<MatchCriterion>  $criteria
     */
    public function __construct(private array $criteria) {}

    public function evaluate(User $user, Opportunity $opportunity): MatchEvaluation
    {
        $user->loadMissing(['skills', 'profile']);
        $opportunity->loadMissing('skills');

        $results = [];

        foreach ($this->criteria as $criterion) {
            $results[] = $criterion->evaluate($user, $opportunity);
        }

        return new MatchEvaluation($results);
    }
}
