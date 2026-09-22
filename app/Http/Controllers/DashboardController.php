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

        return view('dashboard', [
            'counts' => [
                'opportunities' => Opportunity::query()->where('status', OpportunityStatus::Open)->count(),
                'matches' => OpportunityMatch::query()
                    ->where('user_id', $user->id)
                    ->whereNotNull('score')
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
                ->with('opportunity')
                ->where('user_id', $user->id)
                ->whereNotNull('score')
                ->orderByDesc('score')
                ->orderByDesc('id')
                ->limit(5)
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
        ]);
    }
}
