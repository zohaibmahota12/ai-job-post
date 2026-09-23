@extends('layouts.app')

@section('title', $opportunity->title)

@section('content')
    <p class="text-sm font-medium text-bark">{{ $opportunity->source?->name ?? 'Unknown source' }}</p>
    <div class="mt-1 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl font-bold tracking-tight text-pine">{{ $opportunity->title }}</h1>
            <p class="mt-2 text-bark">{{ $opportunity->company ?: 'Client not listed' }}</p>
        </div>
        <div class="rounded-2xl border border-line bg-card px-5 py-4 text-center shadow-sm shadow-ink/5">
            <p class="text-[11px] font-semibold tracking-wider text-bark uppercase">Match score</p>
            <p class="mt-1 font-serif text-3xl font-bold text-moss">{{ $scored->score }}</p>
        </div>
    </div>
    <dl class="mt-6 grid gap-3 rounded-2xl border border-line bg-card p-4 text-sm shadow-sm shadow-ink/5 sm:grid-cols-2">
        <div><dt class="text-bark">Source</dt><dd class="mt-0.5 font-medium text-ink">{{ $opportunity->source?->name ?? 'Unknown source' }}</dd></div>
        <div><dt class="text-bark">Status</dt><dd class="mt-0.5 font-medium text-ink">{{ $opportunity->status->label() }}</dd></div>
        <div><dt class="text-bark">Job type</dt><dd class="mt-0.5 font-medium text-ink">{{ $opportunity->job_type?->label() ?? 'Unknown' }}</dd></div>
        <div><dt class="text-bark">Workplace</dt><dd class="mt-0.5 font-medium text-ink">{{ $opportunity->workplace?->label() ?? 'Unknown' }}</dd></div>
        <div><dt class="text-bark">Location</dt><dd class="mt-0.5 font-medium text-ink">{{ $opportunity->location ?: 'Not listed' }}</dd></div>
        <div><dt class="text-bark">Budget / salary</dt><dd class="mt-0.5 font-medium text-ink">{{ $opportunity->budget_min || $opportunity->budget_max ? trim(($opportunity->budget_min ?? '').'–'.($opportunity->budget_max ?? '').' '.($opportunity->currency ?? '')) : 'Not listed' }}</dd></div>
        <div><dt class="text-bark">Posted</dt><dd class="mt-0.5 font-medium text-ink">{{ $opportunity->posted_at?->toDayDateTimeString() ?? 'Not listed' }}</dd></div>
        <div><dt class="text-bark">Deadline</dt><dd class="mt-0.5 font-medium text-ink">{{ $opportunity->deadline_at?->toDayDateTimeString() ?? 'Not listed' }}</dd></div>
        <div><dt class="text-bark">Original URL</dt><dd class="mt-0.5 font-medium text-ink break-all">{{ $opportunity->safeSourceUrl() ?? 'Not listed' }}</dd></div>
        <div><dt class="text-bark">Match score</dt><dd class="mt-0.5 font-medium text-ink">{{ $scored->score }}</dd></div>
        <div><dt class="text-bark">Application status</dt><dd class="mt-0.5 font-medium text-ink">{{ $application?->status->label() ?? 'Not tracking yet' }}</dd></div>
        <div><dt class="text-bark">Proposal</dt><dd class="mt-0.5 font-medium text-ink">{{ $proposal?->status->label() ?? 'None yet' }}</dd></div>
    </dl>
    @if ($opportunity->skills->isNotEmpty())
        <ul class="mt-4 flex flex-wrap gap-2">
            @foreach ($opportunity->skills as $skill)
                <li class="rounded-md bg-sand px-2.5 py-1 text-sm font-medium text-ink">{{ $skill->name }}</li>
            @endforeach
        </ul>
    @endif
    @if ($opportunity->description)
        <div class="mt-6 max-w-3xl whitespace-pre-line text-sm leading-relaxed text-ink">{{ $opportunity->description }}</div>
    @endif
    @if ($url = $opportunity->safeSourceUrl())
        <p class="mt-4"><a class="text-sm font-medium text-moss underline decoration-moss/30 underline-offset-2 hover:decoration-moss" href="{{ $url }}" rel="noopener noreferrer" target="_blank">Apply externally</a></p>
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
            <a class="inline-flex items-center rounded-lg border border-line bg-card px-4 py-2.5 text-sm font-medium text-ink transition hover:bg-sand" href="{{ route('proposals.show', $proposal) }}">View proposal</a>
        @endif

        @if ($aiEnabled)
            <form method="POST" action="{{ route('opportunities.proposals.generate', $opportunity) }}">
                @csrf
                <x-button variant="secondary">{{ $proposal ? 'Regenerate proposal' : 'Generate proposal' }}</x-button>
            </form>
        @else
            <p class="w-full text-sm text-bark">AI proposal generation is currently unavailable. You can still create and edit a proposal manually.</p>
            @unless ($proposal)
                <a class="inline-flex items-center rounded-lg border border-line bg-card px-4 py-2.5 text-sm font-medium text-ink transition hover:bg-sand" href="{{ route('proposals.create', ['opportunity' => $opportunity->id]) }}">Write a proposal</a>
            @endunless
        @endif

        @unless ($aiEnabled)
            @if ($proposal)
                <a class="inline-flex items-center rounded-lg border border-line bg-card px-4 py-2.5 text-sm font-medium text-ink transition hover:bg-sand" href="{{ route('proposals.edit', $proposal) }}">Edit proposal</a>
            @endif
        @endunless

        @if ($application)
            <a class="inline-flex items-center rounded-lg border border-line bg-card px-4 py-2.5 text-sm font-medium text-ink transition hover:bg-sand" href="{{ route('applications.edit', $application) }}">Track application</a>
        @else
            <a class="inline-flex items-center rounded-lg border border-line bg-card px-4 py-2.5 text-sm font-medium text-ink transition hover:bg-sand" href="{{ route('applications.create', ['opportunity' => $opportunity->id]) }}">Track application</a>
        @endif
    </div>

    <section class="mt-8">
        <h2 class="text-sm font-semibold tracking-wide text-ink uppercase">Match breakdown</h2>
        <p class="mt-1 text-sm text-bark">Deterministic score from your profile. Unknown factors earn 0 points and stay labeled unknown.</p>
        <ul class="mt-4 space-y-2">
            @foreach ($scored->factors as $factor)
                <li class="rounded-xl border border-line bg-card px-4 py-3 text-sm shadow-sm shadow-ink/5">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-medium text-ink">{{ str_replace('_', ' ', ucfirst($factor->key)) }}</span>
                        <span class="text-bark">{{ $factor->score }}/{{ $factor->max }} · {{ $factor->status->label() }}</span>
                    </div>
                    <p class="mt-1 text-bark">{{ $factor->reason }}</p>
                </li>
            @endforeach
        </ul>
    </section>
@endsection
