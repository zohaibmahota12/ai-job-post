@extends('layouts.app')

@section('title', 'Opportunities')

@section('content')
    <h1 class="font-serif text-3xl font-bold tracking-tight text-pine">Opportunities</h1>
    <p class="mt-2 text-sm text-bark">Public listings collected into one list. Saving, drafts, and applications stay on your account.</p>

    <form method="GET" action="{{ route('opportunities.index') }}" class="mt-6 grid gap-3 rounded-2xl border border-line bg-card p-4 shadow-sm shadow-ink/5 sm:grid-cols-2 lg:grid-cols-3">
        <label class="text-sm">
            <span class="font-medium text-ink">Minimum match score</span>
            <input type="number" min="0" max="100" name="min_score" value="{{ $filters['min_score'] }}" class="mt-1.5 w-full rounded-lg border border-line px-3 py-2.5 text-sm outline-none transition focus:border-moss focus:ring-2 focus:ring-moss/20">
        </label>
        <label class="text-sm">
            <span class="font-medium text-ink">Job type</span>
            <select name="job_type" class="mt-1.5 w-full rounded-lg border border-line px-3 py-2.5 text-sm outline-none transition focus:border-moss focus:ring-2 focus:ring-moss/20">
                <option value="">Any</option>
                @foreach ($jobTypes as $type)
                    @if ($type->value !== 'any')
                        <option value="{{ $type->value }}" @selected($filters['job_type'] === $type->value)>{{ $type->label() }}</option>
                    @endif
                @endforeach
            </select>
        </label>
        <label class="text-sm">
            <span class="font-medium text-ink">Workplace</span>
            <select name="workplace" class="mt-1.5 w-full rounded-lg border border-line px-3 py-2.5 text-sm outline-none transition focus:border-moss focus:ring-2 focus:ring-moss/20">
                <option value="">Any</option>
                @foreach ($workplaces as $workplace)
                    @if ($workplace->value !== 'unspecified')
                        <option value="{{ $workplace->value }}" @selected($filters['workplace'] === $workplace->value)>{{ $workplace->label() }}</option>
                    @endif
                @endforeach
            </select>
        </label>
        <label class="text-sm">
            <span class="font-medium text-ink">Location</span>
            <input type="text" name="location" value="{{ $filters['location'] }}" class="mt-1.5 w-full rounded-lg border border-line px-3 py-2.5 text-sm outline-none transition focus:border-moss focus:ring-2 focus:ring-moss/20" placeholder="City or region">
        </label>
        <label class="text-sm">
            <span class="font-medium text-ink">Source</span>
            <select name="source_id" class="mt-1.5 w-full rounded-lg border border-line px-3 py-2.5 text-sm outline-none transition focus:border-moss focus:ring-2 focus:ring-moss/20">
                <option value="">Any</option>
                @foreach ($sources as $source)
                    <option value="{{ $source->id }}" @selected((int) $filters['source_id'] === $source->id)>{{ $source->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">
            <span class="font-medium text-ink">Saved</span>
            <select name="saved" class="mt-1.5 w-full rounded-lg border border-line px-3 py-2.5 text-sm outline-none transition focus:border-moss focus:ring-2 focus:ring-moss/20">
                <option value="">Any</option>
                <option value="1" @selected($filters['saved'] === '1')>Saved only</option>
                <option value="0" @selected($filters['saved'] === '0')>Not saved</option>
            </select>
        </label>
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-3">
            <x-button>Filter</x-button>
            <a href="{{ route('opportunities.index') }}" class="inline-flex items-center rounded-lg border border-line bg-card px-4 py-2.5 text-sm font-medium text-ink transition hover:bg-sand">Clear</a>
        </div>
    </form>

    <div class="mt-6 space-y-2">
        @forelse ($opportunities as $opportunity)
            @php $userMatch = $opportunity->matches->first(); @endphp
            <a href="{{ route('opportunities.show', $opportunity) }}" class="block rounded-2xl border border-line bg-card p-4 shadow-sm shadow-ink/5 transition hover:border-moss/40 hover:shadow-md hover:shadow-ink/5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-ink">{{ $opportunity->title }}</p>
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
                        <div class="shrink-0 text-right">
                            <p class="font-serif text-2xl font-bold text-moss">{{ (int) $userMatch->score }}</p>
                            <p class="text-[11px] font-medium tracking-wide text-bark uppercase">Match</p>
                        </div>
                    @endif
                </div>
            </a>
        @empty
            <x-empty-state title="Nothing matched these filters" body="Enable a configured RSS or JSON source and run collection, or clear filters." />
        @endforelse
    </div>
    <div class="mt-6">{{ $opportunities->links() }}</div>
@endsection
