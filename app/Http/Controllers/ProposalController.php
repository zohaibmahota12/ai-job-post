<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Models\Opportunity;
use App\Models\Proposal;
use App\ProposalStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProposalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $proposals = Proposal::query()
            ->with('opportunity')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(15);

        return view('proposals.index', [
            'proposals' => $proposals,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Proposal::class);

        $opportunity = null;
        $opportunityId = $request->integer('opportunity');

        if ($opportunityId > 0) {
            $opportunity = Opportunity::query()->find($opportunityId);
        }

        return view('proposals.create', [
            'opportunity' => $opportunity,
            'opportunities' => Opportunity::query()->orderBy('title')->orderBy('id')->limit(100)->get(),
        ]);
    }

    public function store(StoreProposalRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $proposal = $user->proposals()->create([
            'opportunity_id' => $request->integer('opportunity_id'),
            'content' => $request->input('content'),
            'status' => $request->enum('status', ProposalStatus::class) ?? ProposalStatus::Draft,
        ]);

        return redirect()->route('proposals.edit', $proposal)->with('status', 'Proposal draft saved.');
    }

    public function edit(Proposal $proposal): View
    {
        $this->authorizeOwned('view', $proposal);
        $proposal->load('opportunity');

        return view('proposals.edit', [
            'proposal' => $proposal,
        ]);
    }

    public function update(UpdateProposalRequest $request, Proposal $proposal): RedirectResponse
    {
        $this->authorizeOwned('update', $proposal);

        $proposal->update([
            'content' => $request->input('content'),
            'status' => $request->enum('status', ProposalStatus::class) ?? $proposal->status,
        ]);

        return redirect()->route('proposals.edit', $proposal)->with('status', 'Proposal updated.');
    }

    public function destroy(Proposal $proposal): RedirectResponse
    {
        $this->authorizeOwned('delete', $proposal);
        $proposal->delete();

        return redirect()->route('proposals.index')->with('status', 'Proposal deleted.');
    }
}
