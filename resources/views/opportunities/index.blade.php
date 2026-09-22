@extends('layouts.app')

@section('title', 'Opportunities')

@section('content')
    <h1 class="font-serif text-3xl text-pine">Opportunities</h1>
    <p class="mt-2 text-sm text-bark">Public listings collected into one list. Saving, drafts, and applications stay on your account.</p>

    <form method="GET" action="{{ route('opportunities.index') }}" class="mt-6 grid gap-3 rounded-xl border border-line bg-card p-4 sm:grid-cols-2 lg:grid-cols-3">
        <label class="text-sm">
            <span class="text-bark">Minimum match score</span>
            <input type="number" min="0" max="100" name="min_score" value="{{ $filters['min_score'] }}" class="mt-1 w-full rounded-md border border-line px-3 py-2">
        </label>
        <label class="text-sm">
            <span class="text-bark">Job type</span>
            <select name="job_type" class="mt-1 w-full rounded-md border border-line px-3 py-2">
                <option value="">Any</option>
                @foreach ($jobTypes as $type)
                    @if ($type->value !== 'any')
                        <option value="{{ $type->value }}" @selected($filters['job_type'] === $type->value)>{{ $type->label() }}</option>
                    @endif
                @endforeach
            </select>
        </label>
        <label class="text-sm">
            <span class="text-bark">Workplace</span>
            <select name="workplace" class="mt-1 w-full rounded-md border border-line px-3 py-2">
                <option value="">Any</option>
                @foreach ($workplaces as $workplace)
                    @if ($workplace->value !== 'unspecified')
                        <option value="{{ $workplace->value }}" @selected($filters['workplace'] === $workplace->value)>{{ $workplace->label() }}</option>
                    @endif
                @endforeach
            </select>
        </label>
        <label class="text-sm">
            <span class="text-bark">Location</span>
            <input type="text" name="location" value="{{ $filters['location'] }}" class="mt-1 w-full rounded-md border border-line px-3 py-2" placeholder="City or region">
        </label>
        <label class="text-sm">
            <span class="text-bark">Source</span>
            <select name="source_id" class="mt-1 w-full rounded-md border border-line px-3 py-2">
                <option value="">Any</option>
                @foreach ($sources as $source)
                    <option value="{{ $source->id }}" @selected((int) $filters['source_id'] === $source->id)>{{ $source->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">
            <span class="text-bark">Saved</span>
            <select name="saved" class="mt-1 w-full rounded-md border border-line px-3 py-2">
                <option value="">Any</option>
                <option value="1" @selected($filters['saved'] === '1')>Saved only</option>
                <option value="0" @selected($filters['saved'] === '0')>Not saved</option>
            </select>
        </label>
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-3">
            <x-button>Filter</x-button>
            <a href="{{ route('opportunities.index') }}" class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm">Clear</a>
        </div>
    </form>

    <div class="mt-6 space-y-3">
        @forelse ($opportunities as $opportunity)
            @php $userMatch = $opportunity->matches->first(); @endphp
            <a href="{{ route('opportunities.show', $opportunity) }}" class="block rounded-xl border border-line bg-card p-4 hover:border-moss">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">{{ $opportunity->title }}</p>
                        <p class="mt-1 text-sm text-bark">
                            {{ $opportunity->company ?: 'Client not listed' }}
                            · {{ $opportunity->source?->name ?? 'Unknown source' }}
                            · {{ $opportunity->job_type?->label() ?? 'Type unknown' }}
                            · {{ $opportunity->workplace?->label() ?? 'Workplace unknown' }}
                        </p>
                        <p class="mt-1 text-sm text-bark">
                            {{ $opportunity->location ?: 'Location not listed' }}
                            @if ($opportunity->posted_at)
                                · Posted {{ $opportunity->posted_at->toFormattedDateString() }}
                            @endif
                            @if ($opportunity->budget_min || $opportunity->budget_max)
                                · {{ trim(($opportunity->budget_min ?? '').'–'.($opportunity->budget_max ?? '').' '.($opportunity->currency ?? '')) }}
                            @endif
                        </p>
                    </div>
                    @if ($userMatch?->score !== null)
                        <p class="shrink-0 font-serif text-2xl text-pine">{{ (int) $userMatch->score }}</p>
                    @endif
                </div>
            </a>
        @empty
            <x-empty-state title="Nothing matched these filters" body="Enable a configured RSS or JSON source and run collection, or clear filters." />
        @endforelse
    </div>
    <div class="mt-6">{{ $opportunities->links() }}</div>
@endsection
