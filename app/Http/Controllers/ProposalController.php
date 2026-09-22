<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Models\Opportunity;
use App\Models\OpportunityMatch;
use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\ProposalStatus;
use App\ProposalVersionSource;
use App\Services\Ai\AiManager;
use App\Services\Ai\Exceptions\AiException;
use App\Services\Notifications\NotificationService;
use App\Services\Proposals\ProposalGenerator;
use App\Services\Proposals\ProposalVersionService;
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
            'aiEnabled' => app(AiManager::class)->enabled(),
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
            'aiEnabled' => app(AiManager::class)->enabled(),
        ]);
    }

    public function store(StoreProposalRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $proposal = $user->proposals()->create([
            'opportunity_id' => $request->integer('opportunity_id'),
            'subject' => $request->input('subject'),
            'content' => $request->input('content'),
            'status' => $request->enum('status', ProposalStatus::class) ?? ProposalStatus::Draft,
        ]);

        if (filled($proposal->content) || filled($proposal->subject)) {
            $proposal->versions()->create([
                'content' => $proposal->content,
                'subject' => $proposal->subject,
                'source' => ProposalVersionSource::User,
            ]);
        }

        return redirect()->route('proposals.show', $proposal)->with('status', 'Proposal draft saved.');
    }

    public function show(Request $request, Proposal $proposal): View
    {
        $this->authorizeOwned('view', $proposal);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $proposal->load(['opportunity.source', 'opportunity.skills', 'versions']);

        $match = OpportunityMatch::query()
            ->where('user_id', $user->id)
            ->where('opportunity_id', $proposal->opportunity_id)
            ->first();

        return view('proposals.show', [
            'proposal' => $proposal,
            'match' => $match,
            'aiEnabled' => app(AiManager::class)->enabled(),
        ]);
    }

    public function edit(Proposal $proposal): View
    {
        $this->authorizeOwned('view', $proposal);
        $proposal->load('opportunity');

        return view('proposals.edit', [
            'proposal' => $proposal,
            'aiEnabled' => app(AiManager::class)->enabled(),
        ]);
    }

    public function update(
        UpdateProposalRequest $request,
        Proposal $proposal,
        ProposalVersionService $versions,
        NotificationService $notifications,
    ): RedirectResponse {
        $this->authorizeOwned('update', $proposal);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $newStatus = $request->enum('status', ProposalStatus::class) ?? $proposal->status;
        $contentChanged = (string) $request->input('content') !== (string) $proposal->content
            || (string) $request->input('subject') !== (string) $proposal->subject;

        if ($contentChanged) {
            $versions->snapshot($proposal, ProposalVersionSource::User);
        }

        $becameReady = $newStatus === ProposalStatus::Ready && $proposal->status !== ProposalStatus::Ready;

        $proposal->update([
            'subject' => $request->input('subject'),
            'content' => $request->input('content'),
            'status' => $newStatus,
        ]);

        if ($contentChanged) {
            $proposal->versions()->create([
                'content' => $proposal->content,
                'subject' => $proposal->subject,
                'source' => ProposalVersionSource::User,
            ]);
        }

        if ($becameReady) {
            $notifications->notify(
                $user,
                'proposal.ready',
                'Proposal marked ready',
                'Your proposal is marked ready for you to apply manually on the original listing.',
                [
                    'proposal_id' => $proposal->id,
                    'opportunity_id' => $proposal->opportunity_id,
                ],
            );
        }

        return redirect()->route('proposals.show', $proposal)->with('status', 'Proposal updated.');
    }

    public function destroy(Proposal $proposal): RedirectResponse
    {
        $this->authorizeOwned('delete', $proposal);
        $proposal->delete();

        return redirect()->route('proposals.index')->with('status', 'Proposal deleted.');
    }

    public function generateForOpportunity(
        Request $request,
        Opportunity $opportunity,
        ProposalGenerator $generator,
    ): RedirectResponse {
        $this->authorize('create', Proposal::class);

        $user = $request->user();
        abort_unless($user !== null, 403);

        try {
            $proposal = $generator->generate($user, $opportunity);
        } catch (AiException $exception) {
            return redirect()
                ->route('opportunities.show', $opportunity)
                ->with('error', $exception->userMessage());
        }

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('status', 'AI draft generated. Review and edit before you apply yourself.');
    }

    public function generate(
        Request $request,
        Proposal $proposal,
        ProposalGenerator $generator,
    ): RedirectResponse {
        $this->authorizeOwned('update', $proposal);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $proposal->load('opportunity');

        try {
            if (filled($proposal->content)) {
                $generator->regenerate($user, $proposal);
            } else {
                $generator->generate($user, $proposal->opportunity, $proposal);
            }
        } catch (AiException $exception) {
            return redirect()
                ->route('proposals.show', $proposal)
                ->with('error', $exception->userMessage());
        }

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('status', 'AI draft generated. Review and edit before you apply yourself.');
    }

    public function regenerate(
        Request $request,
        Proposal $proposal,
        ProposalGenerator $generator,
    ): RedirectResponse {
        $this->authorizeOwned('update', $proposal);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $before = $proposal->content;

        try {
            $generator->regenerate($user, $proposal);
        } catch (AiException $exception) {
            $proposal->refresh();
            abort_unless($proposal->content === $before, 500);

            return redirect()
                ->route('proposals.show', $proposal)
                ->with('error', $exception->userMessage());
        }

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('status', 'Proposal regenerated. Previous version was preserved.');
    }

    public function markReady(
        Request $request,
        Proposal $proposal,
        NotificationService $notifications,
    ): RedirectResponse {
        $this->authorizeOwned('update', $proposal);

        $user = $request->user();
        abort_unless($user !== null, 403);

        if ($proposal->status !== ProposalStatus::Ready) {
            $proposal->forceFill(['status' => ProposalStatus::Ready])->save();

            $notifications->notify(
                $user,
                'proposal.ready',
                'Proposal marked ready',
                'Your proposal is marked ready for you to apply manually on the original listing.',
                [
                    'proposal_id' => $proposal->id,
                    'opportunity_id' => $proposal->opportunity_id,
                ],
            );
        }

        return redirect()->route('proposals.show', $proposal)->with('status', 'Proposal marked ready.');
    }

    public function archive(Proposal $proposal): RedirectResponse
    {
        $this->authorizeOwned('update', $proposal);

        $proposal->forceFill(['status' => ProposalStatus::Archived])->save();

        return redirect()->route('proposals.show', $proposal)->with('status', 'Proposal archived.');
    }

    public function restoreVersion(
        Proposal $proposal,
        ProposalVersion $version,
        ProposalVersionService $versions,
    ): RedirectResponse {
        $this->authorizeOwned('update', $proposal);
        abort_unless($version->proposal_id === $proposal->id, 404);

        $versions->restore($proposal, $version);

        return redirect()->route('proposals.show', $proposal)->with('status', 'Previous version restored as draft.');
    }
}
