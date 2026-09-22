<?php

namespace App\Services\Matching;

use App\CriterionOutcome;

final class FactorScore
{
    public function __construct(
        public string $key,
        public int $score,
        public int $max,
        public CriterionOutcome $status,
        public string $reason,
    ) {}

    /**
     * @return array{score: int, max: int, status: string, reason: string}
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'max' => $this->max,
            'status' => $this->status->value,
            'reason' => $this->reason,
        ];
    }
}
