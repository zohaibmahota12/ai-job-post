<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\JobType;
use App\RemotePreference;
use App\Services\Matching\MatchSynchronizer;
use App\Support\KeywordList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $profile = $user->profile()->firstOrCreate([], [
            'preferred_job_type' => JobType::Any,
            'remote_preference' => RemotePreference::Any,
            'preferred_currency' => 'USD',
            'keywords' => [],
            'excluded_keywords' => [],
        ]);

        $user->load('skills');

        return view('profile.edit', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    public function update(UpdateProfileRequest $request, MatchSynchronizer $matches): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $user->forceFill([
            'name' => $request->string('name')->toString(),
        ])->save();

        $profile = $user->profile()->firstOrCreate([]);
        $profile->fill([
            'bio' => $request->input('bio'),
            'experience' => $request->input('experience'),
            'years_of_experience' => $request->input('years_of_experience'),
            'location' => $request->input('location'),
            'preferred_job_type' => $request->string('preferred_job_type')->toString(),
            'remote_preference' => $request->string('remote_preference')->toString(),
            'minimum_budget' => $request->input('minimum_budget'),
            'preferred_currency' => strtoupper($request->string('preferred_currency')->toString()),
            'keywords' => KeywordList::parse($request->input('keywords')),
            'excluded_keywords' => KeywordList::parse($request->input('excluded_keywords')),
        ])->save();

        $matches->syncUserAgainstOpenOpportunities($user->fresh(['skills', 'profile']));

        return redirect()->route('profile.edit')->with('status', 'Profile saved.');
    }
}
