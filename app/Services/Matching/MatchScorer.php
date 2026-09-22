<?php

namespace App\Services\Matching;

use App\CriterionOutcome;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Support\Str;

class MatchScorer
{
    /**
     * @param  array<string, int>|null  $weights
     */
    public function __construct(private ?array $weights = null)
    {
        $this->weights ??= config('opportunity.scoring.weights', []);
    }

    public function score(User $user, Opportunity $opportunity, MatchEvaluation $evaluation): ScoredMatch
    {
        $user->loadMissing(['skills', 'profile']);
        $opportunity->loadMissing('skills');

        $factors = [
            $this->skills($user, $opportunity, $evaluation),
            $this->keywords($user, $opportunity, $evaluation),
            $this->binary('experience', $evaluation),
            $this->binary('job_type', $evaluation),
            $this->binary('workplace', $evaluation),
            $this->binary('location', $evaluation),
            $this->binary('budget', $evaluation),
        ];

        $total = 0;

        foreach ($factors as $factor) {
            $total += $factor->score;
        }

        return new ScoredMatch(
            score: $total,
            factors: $factors,
            evaluation: $evaluation,
        );
    }

    private function skills(User $user, Opportunity $opportunity, MatchEvaluation $evaluation): FactorScore
    {
        $max = $this->weight('skills');
        $result = $evaluation->resultFor('skills');
        $userCount = $user->skills->count();
        $opportunityCount = $opportunity->skills->count();

        if ($userCount === 0 || $opportunityCount === 0) {
            return new FactorScore(
                key: 'skills',
                score: 0,
                max: $max,
                status: CriterionOutcome::Unknown,
                reason: $result?->detail ?? 'Add skills on both sides before this can be compared.',
            );
        }

        $overlap = $user->skills->pluck('slug')->intersect($opportunity->skills->pluck('slug'))->values();
        $matched = $overlap->count();
        $score = (int) round(($matched / $opportunityCount) * $max);

        if ($matched === 0) {
            return new FactorScore(
                key: 'skills',
                score: 0,
                max: $max,
                status: CriterionOutcome::Fail,
                reason: '0 of '.$opportunityCount.' relevant skills matched',
            );
        }

        return new FactorScore(
            key: 'skills',
            score: $score,
            max: $max,
            status: CriterionOutcome::Pass,
            reason: $matched.' of '.$opportunityCount.' relevant skills matched',
        );
    }

    private function keywords(User $user, Opportunity $opportunity, MatchEvaluation $evaluation): FactorScore
    {
        $max = $this->weight('keywords');
        $result = $evaluation->resultFor('keywords');
        $profile = $user->profile;
        $keywords = $profile?->keywords ?? [];
        $excluded = $profile?->excluded_keywords ?? [];

        if ($keywords === [] && $excluded === []) {
            return new FactorScore(
                key: 'keywords',
                score: 0,
                max: $max,
                status: CriterionOutcome::Unknown,
                reason: $result?->detail ?? 'No keywords are set on the profile.',
            );
        }

        $haystack = Str::lower(trim($opportunity->title.' '.$opportunity->description));

        foreach ($excluded as $keyword) {
            if (is_string($keyword) && trim($keyword) !== '' && Str::contains($haystack, Str::lower(trim($keyword)))) {
                return new FactorScore(
                    key: 'keywords',
                    score: 0,
                    max: $max,
                    status: CriterionOutcome::Fail,
                    reason: 'Excluded keyword found: '.$keyword.'.',
                );
            }
        }

        if ($keywords === []) {
            return new FactorScore(
                key: 'keywords',
                score: 0,
                max: $max,
                status: CriterionOutcome::Unknown,
                reason: $result?->detail ?? 'No preferred keywords are set.',
            );
        }

        $matched = [];

        foreach ($keywords as $keyword) {
            if (is_string($keyword) && trim($keyword) !== '' && Str::contains($haystack, Str::lower(trim($keyword)))) {
                $matched[] = trim($keyword);
            }
        }

        $score = (int) round((count($matched) / count($keywords)) * $max);

        if ($matched === []) {
            return new FactorScore(
                key: 'keywords',
                score: 0,
                max: $max,
                status: CriterionOutcome::Fail,
                reason: '0 of '.count($keywords).' preferred keywords matched',
            );
        }

        return new FactorScore(
            key: 'keywords',
            score: $score,
            max: $max,
            status: CriterionOutcome::Pass,
            reason: count($matched).' of '.count($keywords).' preferred keywords matched',
        );
    }

    private function binary(string $key, MatchEvaluation $evaluation): FactorScore
    {
        $max = $this->weight($key);
        $result = $evaluation->resultFor($key);
        $outcome = $result?->outcome ?? CriterionOutcome::Unknown;
        $reason = $result?->detail ?? 'Not evaluated.';

        return new FactorScore(
            key: $key,
            score: $outcome === CriterionOutcome::Pass ? $max : 0,
            max: $max,
            status: $outcome,
            reason: $reason,
        );
    }

    private function weight(string $key): int
    {
        return (int) ($this->weights[$key] ?? 0);
    }
}
