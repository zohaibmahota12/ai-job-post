<?php

namespace App\Services\Matching;

use App\Models\Opportunity;
use App\Models\User;

interface MatchCriterion
{
    public function evaluate(User $user, Opportunity $opportunity): CriterionResult;
}
