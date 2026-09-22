<?php

namespace App\Http\Controllers;

use App\JobType;
use App\Models\Application;
use App\Models\Opportunity;
use App\Models\Proposal;
use App\Models\SavedOpportunity;
use App\Models\Source;
use App\OpportunityStatus;
use App\Services\Ai\AiManager;
use App\Services\Matching\MatchEvaluator;
use App\Services\Matching\MatchScorer;
use App\Services\Matching\MatchSynchronizer;
use App\Workplace;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Opportunity::class);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $filters = [
            'min_score' => $request->integer('min_score', 0) ?: null,
            'job_type' => $request->string('job_type')->toString() ?: null,
            'workplace' => $request->string('workplace')->toString() ?: null,
            'location' => $request->string('location')->toString() ?: null,
            'source_id' => $request->integer('source_id') ?: null,
            'saved' => $request->string('saved')->toString() ?: null,
        ];

        $query = Opportunity::query()
            ->with(['source', 'skills'])
            ->with(['matches' => fn ($matchQuery) => $matchQuery->where('user_id', $user->id)])
            ->where('status', OpportunityStatus::Open);

        if ($filters['job_type'] !== null && JobType::tryFrom($filters['job_type']) !== null) {
            $query->where('job_type', $filters['job_type']);
        }

        if ($filters['workplace'] !== null && Workplace::tryFrom($filters['workplace']) !== null) {
            $query->where('workplace', $filters['workplace']);
        }

        if ($filters['location'] !== null) {
            $query->where('location', 'like', '%'.$filters['location'].'%');
        }

        if ($filters['source_id'] !== null) {
            $query->where('source_id', $filters['source_id']);
        }

        if ($filters['saved'] === '1') {
            $query->whereHas('savedBy', fn ($saved) => $saved->where('user_id', $user->id));
        } elseif ($filters['saved'] === '0') {
            $query->whereDoesntHave('savedBy', fn ($saved) => $saved->where('user_id', $user->id));
        }

        if ($filters['min_score'] !== null) {
            $query->whereHas('matches', function ($matchQuery) use ($user, $filters) {
                $matchQuery->where('user_id', $user->id)
                    ->where('score', '>=', $filters['min_score']);
            });
        }

        $opportunities = $query
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('opportunities.index', [
            'opportunities' => $opportunities,
            'filters' => $filters,
            'sources' => Source::query()->orderBy('name')->get(['id', 'name']),
            'jobTypes' => JobType::cases(),
            'workplaces' => Workplace::cases(),
        ]);
    }

    public function show(
        Request $request,
        Opportunity $opportunity,
        MatchEvaluator $evaluator,
        MatchScorer $scorer,
        MatchSynchronizer $synchronizer,
    ): View {
        $this->authorize('view', $opportunity);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $opportunity->load(['source', 'skills']);
        $match = $synchronizer->syncPair($user, $opportunity);
        $evaluation = $evaluator->evaluate($user, $opportunity);
        $scored = $scorer->score($user, $opportunity, $evaluation);

        return view('opportunities.show', [
            'opportunity' => $opportunity,
            'evaluation' => $evaluation,
            'scored' => $scored,
            'match' => $match,
            'saved' => SavedOpportunity::query()
                ->where('user_id', $user->id)
                ->where('opportunity_id', $opportunity->id)
                ->first(),
            'proposal' => Proposal::query()
                ->where('user_id', $user->id)
                ->where('opportunity_id', $opportunity->id)
                ->latest('id')
                ->first(),
            'application' => Application::query()
                ->where('user_id', $user->id)
                ->where('opportunity_id', $opportunity->id)
                ->first(),
            'aiEnabled' => app(AiManager::class)->enabled(),
        ]);
    }
}
