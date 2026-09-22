<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\SavedOpportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedOpportunityController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $saved = SavedOpportunity::query()
            ->with('opportunity.source')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(15);

        return view('saved.index', [
            'savedOpportunities' => $saved,
        ]);
    }

    public function store(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $this->authorize('view', $opportunity);

        $user = $request->user();
        abort_unless($user !== null, 403);

        SavedOpportunity::query()->firstOrCreate([
            'user_id' => $user->id,
            'opportunity_id' => $opportunity->id,
        ]);

        return back()->with('status', 'Opportunity saved.');
    }

    public function destroy(Request $request, SavedOpportunity $savedOpportunity): RedirectResponse
    {
        $this->authorizeOwned('delete', $savedOpportunity);
        $savedOpportunity->delete();

        return back()->with('status', 'Removed from saved opportunities.');
    }
}
