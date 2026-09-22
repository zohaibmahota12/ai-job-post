<?php

namespace App\Http\Controllers;

use App\ApplicationStatus;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationRequest;
use App\Models\Application;
use App\Models\Opportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $applications = Application::query()
            ->with('opportunity')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(15);

        return view('applications.index', [
            'applications' => $applications,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Application::class);

        $opportunity = null;
        $opportunityId = $request->integer('opportunity');

        if ($opportunityId > 0) {
            $opportunity = Opportunity::query()->find($opportunityId);
        }

        return view('applications.create', [
            'opportunity' => $opportunity,
            'opportunities' => Opportunity::query()->orderBy('title')->orderBy('id')->limit(100)->get(),
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $status = $request->enum('status', ApplicationStatus::class) ?? ApplicationStatus::New;
        $opportunityId = $request->integer('opportunity_id');

        $existing = $user->applications()->where('opportunity_id', $opportunityId)->first();

        if ($existing !== null) {
            return redirect()
                ->route('applications.edit', $existing)
                ->with('status', 'You are already tracking this opportunity.');
        }

        $application = $user->applications()->create([
            'opportunity_id' => $opportunityId,
            'proposal_id' => $request->input('proposal_id'),
            'status' => $status,
            'notes' => $request->input('notes'),
            'applied_at' => $status === ApplicationStatus::Applied ? now() : null,
        ]);

        return redirect()->route('applications.edit', $application)->with('status', 'Application tracked. Nothing was submitted for you.');
    }

    public function edit(Application $application): View
    {
        $this->authorizeOwned('view', $application);
        $application->load(['opportunity', 'proposal']);

        return view('applications.edit', [
            'application' => $application,
        ]);
    }

    public function update(UpdateApplicationRequest $request, Application $application): RedirectResponse
    {
        $this->authorizeOwned('update', $application);

        $status = $request->enum('status', ApplicationStatus::class) ?? $application->status;
        $appliedAt = $application->applied_at;

        if ($status === ApplicationStatus::Applied && $appliedAt === null) {
            $appliedAt = now();
        }

        $application->update([
            'proposal_id' => $request->input('proposal_id'),
            'status' => $status,
            'notes' => $request->input('notes'),
            'applied_at' => $appliedAt,
        ]);

        return redirect()->route('applications.edit', $application)->with('status', 'Application status updated.');
    }
}
