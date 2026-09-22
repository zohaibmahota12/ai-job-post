<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSkillRequest;
use App\Models\Skill;
use App\Services\Matching\MatchSynchronizer;
use App\Services\Skills\SkillAttacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ProfileSkillController extends Controller
{
    public function store(StoreSkillRequest $request, SkillAttacher $attacher, MatchSynchronizer $matches): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        try {
            $attacher->attachToUser($user, $request->string('name')->toString());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['name' => $exception->getMessage()])->withInput();
        }

        $matches->syncUserAgainstOpenOpportunities($user->fresh(['skills', 'profile']));

        return back()->with('status', 'Skill added.');
    }

    public function destroy(Request $request, Skill $skill, MatchSynchronizer $matches): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $user->skills()->detach($skill->id);
        $matches->syncUserAgainstOpenOpportunities($user->fresh(['skills', 'profile']));

        return back()->with('status', 'Skill removed.');
    }
}
