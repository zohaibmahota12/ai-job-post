<?php

namespace App\Services\Matching;

use App\CriterionOutcome;

final class CriterionResult
{
    public function __construct(
        public string $key,
        public string $label,
        public CriterionOutcome $outcome,
        public string $detail,
    ) {}
}
