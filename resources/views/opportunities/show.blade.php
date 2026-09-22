@extends('layouts.app')

@section('title', $opportunity->title)

@section('content')
    <p class="text-sm text-bark">{{ $opportunity->source?->name ?? 'Unknown source' }}</p>
    <div class="mt-1 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl text-pine">{{ $opportunity->title }}</h1>
            <p class="mt-2 text-bark">{{ $opportunity->company ?: 'Client not listed' }}</p>
        </div>
        <div class="rounded-xl border border-line bg-card px-4 py-3 text-center">
            <p class="text-sm text-bark">Match score</p>
            <p class="font-serif text-3xl text-pine">{{ $scored->score }}</p>
        </div>
    </div>
    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
        <div><dt class="text-bark">Job type</dt><dd>{{ $opportunity->job_type?->label() ?? 'Unknown' }}</dd></div>
        <div><dt class="text-bark">Workplace</dt><dd>{{ $opportunity->workplace?->label() ?? 'Unknown' }}</dd></div>
        <div><dt class="text-bark">Location</dt><dd>{{ $opportunity->location ?: 'Not listed' }}</dd></div>
        <div><dt class="text-bark">Budget / salary</dt><dd>{{ $opportunity->budget_min || $opportunity->budget_max ? trim(($opportunity->budget_min ?? '').'–'.($opportunity->budget_max ?? '').' '.($opportunity->currency ?? '')) : 'Not listed' }}</dd></div>
        <div><dt class="text-bark">Posted</dt><dd>{{ $opportunity->posted_at?->toDayDateTimeString() ?? 'Not listed' }}</dd></div>
        <div><dt class="text-bark">Deadline</dt><dd>{{ $opportunity->deadline_at?->toDayDateTimeString() ?? 'Not listed' }}</dd></div>
        <div><dt class="text-bark">Application status</dt><dd>{{ $application?->status->label() ?? 'Not tracking yet' }}</dd></div>
        <div><dt class="text-bark">Proposal</dt><dd>{{ $proposal?->status->label() ?? 'None yet' }}</dd></div>
    </dl>
    @if ($opportunity->skills->isNotEmpty())
        <ul class="mt-4 flex flex-wrap gap-2">
            @foreach ($opportunity->skills as $skill)
                <li class="rounded-full bg-sand px-3 py-1 text-sm">{{ $skill->name }}</li>
            @endforeach
        </ul>
    @endif
    @if ($opportunity->description)
        <div class="mt-6 max-w-3xl whitespace-pre-line text-sm leading-relaxed">{{ $opportunity->description }}</div>
    @endif
    @if ($url = $opportunity->safeSourceUrl())
        <p class="mt-4"><a class="text-sm underline" href="{{ $url }}" rel="noopener noreferrer" target="_blank">Apply externally</a></p>
        <p class="mt-1 text-sm text-bark">Apply on the original source. Opportunity Hunter does not auto-apply.</p>
    @endif

    <div class="mt-6 flex flex-wrap gap-3">
        @if ($saved)
            <form method="POST" action="{{ route('saved.destroy', $saved) }}">
                @csrf
                @method('DELETE')
                <x-button variant="secondary">Unsave</x-button>
            </form>
        @else
            <form method="POST" action="{{ route('opportunities.save', $opportunity) }}">
                @csrf
                <x-button>Save</x-button>
            </form>
        @endif

        @if ($proposal)
            <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ route('proposals.show', $proposal) }}">View proposal</a>
        @endif

        @if ($aiEnabled)
            <form method="POST" action="{{ route('opportunities.proposals.generate', $opportunity) }}">
                @csrf
                <x-button variant="secondary">{{ $proposal ? 'Regenerate proposal' : 'Generate proposal' }}</x-button>
            </form>
        @else
            <p class="w-full text-sm text-bark">AI proposal generation is currently unavailable. You can still create and edit a proposal manually.</p>
            @unless ($proposal)
                <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ route('proposals.create', ['opportunity' => $opportunity->id]) }}">Write a proposal</a>
            @endunless
        @endif

        @unless ($aiEnabled)
            @if ($proposal)
                <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ route('proposals.edit', $proposal) }}">Edit proposal</a>
            @endif
        @endunless

        @if ($application)
            <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ route('applications.edit', $application) }}">Track application</a>
        @else
            <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ route('applications.create', ['opportunity' => $opportunity->id]) }}">Track application</a>
        @endif
    </div>

    <section class="mt-8">
        <h2 class="font-serif text-2xl text-pine">Match breakdown</h2>
        <p class="mt-1 text-sm text-bark">Deterministic score from your profile. Unknown factors earn 0 points and stay labeled unknown.</p>
        <ul class="mt-4 space-y-2">
            @foreach ($scored->factors as $factor)
                <li class="rounded-lg border border-line bg-card px-4 py-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-medium">{{ str_replace('_', ' ', ucfirst($factor->key)) }}</span>
                        <span>{{ $factor->score }}/{{ $factor->max }} · {{ $factor->status->label() }}</span>
                    </div>
                    <p class="mt-1 text-bark">{{ $factor->reason }}</p>
                </li>
            @endforeach
        </ul>
    </section>
@endsection
