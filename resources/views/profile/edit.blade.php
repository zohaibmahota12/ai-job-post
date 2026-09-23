@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <h1 class="font-serif text-3xl font-bold tracking-tight text-pine">Your profile</h1>
    <p class="mt-2 max-w-2xl text-sm text-bark">These preferences belong to this account. Matching uses them later. Nothing here is shared with other users.</p>

    <form method="POST" action="{{ route('profile.update') }}" class="mt-6 grid gap-4 rounded-2xl border border-line bg-card p-5 shadow-sm shadow-ink/5 lg:grid-cols-2">
        @csrf
        @method('PUT')
        <x-field label="Name" name="name" value="{{ old('name', $user->name) }}" required />
        <x-field label="Location" name="location" value="{{ old('location', $profile->location) }}" />
        <div class="lg:col-span-2">
            <x-field label="Bio" name="bio" type="textarea">{{ old('bio', $profile->bio) }}</x-field>
        </div>
        <div class="lg:col-span-2">
            <x-field label="Experience" name="experience" type="textarea">{{ old('experience', $profile->experience) }}</x-field>
        </div>
        <x-field label="Years of experience" name="years_of_experience" type="number" min="0" max="80" value="{{ old('years_of_experience', $profile->years_of_experience) }}" />
        <div>
            <label for="preferred_job_type" class="block text-sm font-medium text-ink">Preferred job type</label>
            <select id="preferred_job_type" name="preferred_job_type" class="mt-1.5 w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm outline-none transition focus:border-moss focus:ring-2 focus:ring-moss/20">
                @foreach (\App\JobType::options() as $value => $label)
                    <option value="{{ $value }}" @selected(old('preferred_job_type', $profile->preferred_job_type?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="remote_preference" class="block text-sm font-medium text-ink">Remote preference</label>
            <select id="remote_preference" name="remote_preference" class="mt-1.5 w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm outline-none transition focus:border-moss focus:ring-2 focus:ring-moss/20">
                @foreach (\App\RemotePreference::options() as $value => $label)
                    <option value="{{ $value }}" @selected(old('remote_preference', $profile->remote_preference?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <x-field label="Minimum budget" name="minimum_budget" type="number" step="0.01" min="0" value="{{ old('minimum_budget', $profile->minimum_budget) }}" />
        <x-field label="Preferred currency" name="preferred_currency" maxlength="3" value="{{ old('preferred_currency', $profile->preferred_currency) }}" required />
        <x-field label="Keywords" name="keywords" value="{{ old('keywords', \App\Support\KeywordList::display($profile->keywords)) }}" />
        <x-field label="Excluded keywords" name="excluded_keywords" value="{{ old('excluded_keywords', \App\Support\KeywordList::display($profile->excluded_keywords)) }}" />
        <div class="lg:col-span-2">
            <x-button>Save profile</x-button>
        </div>
    </form>

    <section class="mt-10">
        <h2 class="text-sm font-semibold tracking-wide text-ink uppercase">Skills</h2>
        <p class="mt-1 text-sm text-bark">Skills are shared names, not a fixed catalog. Add the ones you want this account matched against.</p>
        <ul class="mt-4 flex flex-wrap gap-2">
            @forelse ($user->skills as $skill)
                <li class="flex items-center gap-2 rounded-md bg-sand px-3 py-1.5 text-sm font-medium text-ink">
                    {{ $skill->name }}
                    <form method="POST" action="{{ route('profile.skills.destroy', $skill) }}">
                        @csrf
                        @method('DELETE')
                        <button class="text-clay hover:underline" type="submit">Remove</button>
                    </form>
                </li>
            @empty
                <li class="text-sm text-bark">No skills yet.</li>
            @endforelse
        </ul>
        <form method="POST" action="{{ route('profile.skills.store') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <div class="w-full sm:max-w-sm">
                <x-field id="skill-name" label="Add a skill" name="name" value="{{ old('name') }}" required />
            </div>
            <x-button>Add skill</x-button>
        </form>
    </section>
@endsection
