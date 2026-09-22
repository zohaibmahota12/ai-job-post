<?php

namespace App\Services\Proposals;

use App\Models\Opportunity;
use App\Models\OpportunityMatch;
use App\Models\User;

class ProposalContextBuilder
{
    /**
     * Build verified user + opportunity context for proposal generation.
     * Omits missing fields instead of inventing them.
     *
     * @return array{
     *     user: array<string, mixed>,
     *     opportunity: array<string, mixed>,
     *     match: array<string, mixed>|null
     * }
     */
    public function build(User $user, Opportunity $opportunity): array
    {
        $user->loadMissing(['profile', 'skills']);
        $opportunity->loadMissing(['source', 'skills']);

        $profile = $user->profile;
        $match = OpportunityMatch::query()
            ->where('user_id', $user->id)
            ->where('opportunity_id', $opportunity->id)
            ->first();

        $userContext = array_filter([
            'name' => $user->name,
            'bio' => $profile?->bio,
            'experience' => $profile?->experience,
            'years_of_experience' => $profile?->years_of_experience,
            'location' => $profile?->location,
            'preferred_job_type' => $profile?->preferred_job_type?->value,
            'remote_preference' => $profile?->remote_preference?->value,
            'preferred_currency' => $profile?->preferred_currency,
            'skills' => $user->skills->pluck('name')->values()->all(),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);

        $opportunityContext = array_filter([
            'title' => $opportunity->title,
            'description' => $opportunity->description,
            'company' => $opportunity->company,
            'location' => $opportunity->location,
            'job_type' => $opportunity->job_type?->value,
            'workplace' => $opportunity->workplace?->value,
            'budget_min' => $opportunity->budget_min,
            'budget_max' => $opportunity->budget_max,
            'currency' => $opportunity->currency,
            'source' => $opportunity->source?->name,
            'url' => $opportunity->safeSourceUrl(),
            'skills' => $opportunity->skills->pluck('name')->values()->all(),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);

        $matchContext = null;

        if ($match !== null) {
            $reasons = is_array($match->reasons) ? $match->reasons : [];
            $factors = is_array($reasons['factors'] ?? null) ? $reasons['factors'] : $reasons;
            $matchedSkills = $user->skills
                ->pluck('name')
                ->intersect($opportunity->skills->pluck('name'))
                ->values()
                ->all();

            $breakdown = [];

            foreach ($factors as $key => $factor) {
                if (! is_array($factor) || ! isset($factor['score'])) {
                    continue;
                }

                $breakdown[$key] = array_filter([
                    'score' => $factor['score'] ?? null,
                    'max' => $factor['max'] ?? null,
                    'status' => $factor['status'] ?? null,
                    'reason' => $factor['reason'] ?? null,
                ], fn ($value) => $value !== null && $value !== '');
            }

            $matchContext = array_filter([
                'score' => $match->score,
                'matched_skills' => $matchedSkills,
                'breakdown' => $breakdown,
            ], fn ($value) => $value !== null && $value !== []);
        }

        return [
            'user' => $userContext,
            'opportunity' => $opportunityContext,
            'match' => $matchContext,
        ];
    }
}
