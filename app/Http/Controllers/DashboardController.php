<?php

namespace App\Http\Controllers;

use App\ApplicationStatus;
use App\Models\Application;
use App\Models\Opportunity;
use App\Models\OpportunityMatch;
use App\Models\Proposal;
use App\Models\SavedOpportunity;
use App\Models\UserNotification;
use App\OpportunityStatus;
use App\ProposalStatus;
use App\Services\Ai\AiManager;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $minimumScore = (int) config('opportunity.scoring.dashboard_minimum', 50);

        $matches = OpportunityMatch::query()
            ->with(['opportunity.source', 'opportunity.skills'])
            ->where('user_id', $user->id)
            ->whereNotNull('score')
            ->where('score', '>=', $minimumScore)
            ->orderByDesc('score')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $opportunityIds = $matches->pluck('opportunity_id')->all();

        $proposalsByOpportunity = Proposal::query()
            ->where('user_id', $user->id)
            ->whereIn('opportunity_id', $opportunityIds)
            ->orderByDesc('id')
            ->get()
            ->unique('opportunity_id')
            ->keyBy('opportunity_id');

        $applicationsByOpportunity = Application::query()
            ->where('user_id', $user->id)
            ->whereIn('opportunity_id', $opportunityIds)
            ->get()
            ->keyBy('opportunity_id');

        return view('dashboard', [
            'counts' => [
                'opportunities' => Opportunity::query()->where('status', OpportunityStatus::Open)->count(),
                'matches' => OpportunityMatch::query()
                    ->where('user_id', $user->id)
                    ->whereNotNull('score')
                    ->where('score', '>=', $minimumScore)
                    ->count(),
                'saved' => SavedOpportunity::query()->where('user_id', $user->id)->count(),
                'proposals' => Proposal::query()->where('user_id', $user->id)->count(),
                'draft_proposals' => Proposal::query()
                    ->where('user_id', $user->id)
                    ->where('status', ProposalStatus::Draft)
                    ->count(),
                'ready_proposals' => Proposal::query()
                    ->where('user_id', $user->id)
                    ->where('status', ProposalStatus::Ready)
                    ->count(),
                'applications' => Application::query()->where('user_id', $user->id)->count(),
                'applied' => Application::query()
                    ->where('user_id', $user->id)
                    ->where('status', ApplicationStatus::Applied)
                    ->count(),
                'interview' => Application::query()
                    ->where('user_id', $user->id)
                    ->where('status', ApplicationStatus::Interview)
                    ->count(),
                'hired' => Application::query()
                    ->where('user_id', $user->id)
                    ->where('status', ApplicationStatus::Hired)
                    ->count(),
                'rejected' => Application::query()
                    ->where('user_id', $user->id)
                    ->where('status', ApplicationStatus::Rejected)
                    ->count(),
                'notifications' => UserNotification::query()
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count(),
            ],
            'opportunities' => Opportunity::query()
                ->with('source')
                ->where('status', OpportunityStatus::Open)
                ->orderByDesc('posted_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'matches' => $matches,
            'proposalsByOpportunity' => $proposalsByOpportunity,
            'applicationsByOpportunity' => $applicationsByOpportunity,
            'saved' => SavedOpportunity::query()
                ->with('opportunity')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'draftProposals' => Proposal::query()
                ->with('opportunity')
                ->where('user_id', $user->id)
                ->where('status', ProposalStatus::Draft)
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(),
            'readyProposals' => Proposal::query()
                ->with('opportunity')
                ->where('user_id', $user->id)
                ->where('status', ProposalStatus::Ready)
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(),
            'recentGeneratedProposals' => Proposal::query()
                ->with('opportunity')
                ->where('user_id', $user->id)
                ->where('generated_by_ai', true)
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(),
            'applications' => Application::query()
                ->with('opportunity')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit(8)
                ->get(),
            'notifications' => UserNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'minimumScore' => $minimumScore,
            'aiEnabled' => app(AiManager::class)->enabled(),
        ]);
    }
}
