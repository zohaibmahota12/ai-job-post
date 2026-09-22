<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Opportunity;
use App\Models\OpportunityMatch;
use App\Models\Proposal;
use App\Models\SavedOpportunity;
use App\Models\UserNotification;
use App\OpportunityStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $minimumScore = (int) config('opportunity.scoring.dashboard_minimum', 50);

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
                'applications' => Application::query()->where('user_id', $user->id)->count(),
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
            'matches' => OpportunityMatch::query()
                ->with(['opportunity.source'])
                ->where('user_id', $user->id)
                ->whereNotNull('score')
                ->where('score', '>=', $minimumScore)
                ->orderByDesc('score')
                ->orderByDesc('id')
                ->limit(8)
                ->get(),
            'saved' => SavedOpportunity::query()
                ->with('opportunity')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'proposals' => Proposal::query()
                ->with('opportunity')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'applications' => Application::query()
                ->with('opportunity')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'notifications' => UserNotification::query()
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'minimumScore' => $minimumScore,
        ]);
    }
}
