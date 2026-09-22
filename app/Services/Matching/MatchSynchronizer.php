<?php

namespace App\Services\Matching;

use App\Models\Opportunity;
use App\Models\OpportunityMatch;
use App\Models\User;
use App\OpportunityStatus;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MatchSynchronizer
{
    public function __construct(
        private MatchEvaluator $evaluator,
        private MatchScorer $scorer,
    ) {}

    /**
     * Recalculate and persist matches for one user against the given opportunities.
     *
     * @param  Collection<int, Opportunity>|EloquentCollection<int, Opportunity>|iterable<Opportunity>  $opportunities
     * @return int Number of match rows upserted
     */
    public function syncUser(User $user, iterable $opportunities): int
    {
        $count = 0;

        foreach ($opportunities as $opportunity) {
            $this->syncPair($user, $opportunity);
            $count++;
        }

        return $count;
    }

    /**
     * Recalculate matches for every active verified user against the given opportunities.
     *
     * @param  Collection<int, Opportunity>|EloquentCollection<int, Opportunity>|iterable<Opportunity>  $opportunities
     */
    public function syncOpportunities(iterable $opportunities): int
    {
        $opportunityList = collect($opportunities)->unique('id')->values();

        if ($opportunityList->isEmpty()) {
            return 0;
        }

        $users = User::query()
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->with(['skills', 'profile'])
            ->orderBy('id')
            ->get();

        $count = 0;

        foreach ($users as $user) {
            $count += $this->syncUser($user, $opportunityList);
        }

        return $count;
    }

    /**
     * Recalculate this user's matches against all open opportunities.
     */
    public function syncUserAgainstOpenOpportunities(User $user): int
    {
        $opportunities = Opportunity::query()
            ->with('skills')
            ->where('status', OpportunityStatus::Open)
            ->orderBy('id')
            ->get();

        return $this->syncUser($user, $opportunities);
    }

    public function syncPair(User $user, Opportunity $opportunity): OpportunityMatch
    {
        $evaluation = $this->evaluator->evaluate($user, $opportunity);
        $scored = $this->scorer->score($user, $opportunity, $evaluation);
        $payload = $scored->toArray();

        return DB::transaction(function () use ($user, $opportunity, $scored, $payload): OpportunityMatch {
            $match = OpportunityMatch::query()->firstOrNew([
                'user_id' => $user->id,
                'opportunity_id' => $opportunity->id,
            ]);

            $match->forceFill([
                'score' => $scored->score,
                'reasons' => $payload,
                'matched_at' => now(),
            ])->save();

            return $match->refresh();
        });
    }
}
