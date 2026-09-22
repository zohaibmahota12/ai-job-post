<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Opportunity;
use App\Models\Proposal;
use App\Models\SavedOpportunity;
use App\OpportunityStatus;
use App\Services\Matching\MatchEvaluator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Opportunity::class);

        $opportunities = Opportunity::query()
            ->with(['source', 'skills'])
            ->where('status', OpportunityStatus::Open)
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('opportunities.index', [
            'opportunities' => $opportunities,
        ]);
    }

    public function show(Request $request, Opportunity $opportunity, MatchEvaluator $evaluator): View
    {
        $this->authorize('view', $opportunity);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $opportunity->load(['source', 'skills']);

        return view('opportunities.show', [
            'opportunity' => $opportunity,
            'evaluation' => $evaluator->evaluate($user, $opportunity),
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
        ]);
    }
}
