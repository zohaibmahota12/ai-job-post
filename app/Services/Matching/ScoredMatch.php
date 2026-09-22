<?php

namespace App\Services\Matching;

final class ScoredMatch
{
    /**
     * @param  list<FactorScore>  $factors
     */
    public function __construct(
        public int $score,
        public array $factors,
        public MatchEvaluation $evaluation,
    ) {}

    /**
     * @return array{score: int, factors: array<string, array{score: int, max: int, status: string, reason: string}>}
     */
    public function toArray(): array
    {
        $factors = [];

        foreach ($this->factors as $factor) {
            $factors[$factor->key] = $factor->toArray();
        }

        return [
            'score' => $this->score,
            'factors' => $factors,
        ];
    }

    public function factor(string $key): ?FactorScore
    {
        foreach ($this->factors as $factor) {
            if ($factor->key === $key) {
                return $factor;
            }
        }

        return null;
    }
}
