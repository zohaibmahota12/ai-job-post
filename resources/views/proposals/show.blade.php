@extends('layouts.app')

@section('title', 'Proposal review')

@section('content')
    @php
        $opportunity = $proposal->opportunity;
        $url = $opportunity?->safeSourceUrl();
        $reasons = is_array($match?->reasons) ? ($match->reasons['factors'] ?? $match->reasons) : [];
        if (! is_array($reasons)) {
            $reasons = [];
        }
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm text-bark">{{ $opportunity?->source?->name ?? 'Unknown source' }}</p>
            <h1 class="font-serif text-3xl text-pine">Proposal review</h1>
            <p class="mt-2 text-bark">{{ $opportunity?->title }}</p>
        </div>
        <div class="rounded-xl border border-line bg-card px-4 py-3 text-sm">
            <p><span class="text-bark">Status:</span> {{ $proposal->status->label() }}</p>
            <p class="mt-1"><span class="text-bark">Origin:</span> {{ $proposal->generated_by_ai ? 'AI assisted' : 'Manual' }}</p>
            @if ($proposal->ai_provider)
                <p class="mt-1 text-bark">{{ $proposal->ai_provider }}{{ $proposal->ai_model ? ' · '.$proposal->ai_model : '' }}</p>
            @endif
        </div>
    </div>

    @unless ($aiEnabled)
        <p class="mt-4 rounded-md border border-line bg-sand/60 px-3 py-2 text-sm text-bark">AI proposal generation is currently unavailable. You can still create and edit a proposal manually.</p>
    @endunless

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="space-y-4">
            <h2 class="font-serif text-2xl text-pine">Opportunity</h2>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-bark">Company</dt><dd>{{ $opportunity?->company ?: 'Client not listed' }}</dd></div>
                <div><dt class="text-bark">Match score</dt><dd>{{ $match?->score !== null ? (int) $match->score : 'Not scored' }}</dd></div>
                <div><dt class="text-bark">Job type</dt><dd>{{ $opportunity?->job_type?->label() ?? 'Unknown' }}</dd></div>
                <div><dt class="text-bark">Location</dt><dd>{{ $opportunity?->location ?: 'Not listed' }}</dd></div>
            </dl>
            @if ($opportunity?->skills?->isNotEmpty())
                <ul class="flex flex-wrap gap-2">
                    @foreach ($opportunity->skills as $skill)
                        <li class="rounded-full bg-sand px-3 py-1 text-sm">{{ $skill->name }}</li>
                    @endforeach
                </ul>
            @endif
            @if ($reasons !== [])
                <ul class="space-y-2 text-sm">
                    @foreach ($reasons as $key => $factor)
                        @continue(! is_array($factor))
                        <li class="rounded-lg border border-line bg-card px-3 py-2">
                            <span class="font-medium">{{ str_replace('_', ' ', ucfirst((string) $key)) }}</span>
                            <span class="text-bark"> · {{ $factor['score'] ?? 0 }}/{{ $factor['max'] ?? 0 }}</span>
                            @if (! empty($factor['reason']))
                                <p class="mt-1 text-bark">{{ $factor['reason'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($url)
                <p><a class="text-sm underline" href="{{ $url }}" rel="noopener noreferrer" target="_blank">Open original listing</a></p>
                <p class="text-sm text-bark">Apply externally yourself. Opportunity Hunter does not submit applications.</p>
            @endif
        </section>

        <section class="space-y-4">
            <h2 class="font-serif text-2xl text-pine">Proposal</h2>
            @if ($proposal->subject)
                <p class="text-sm"><span class="text-bark">Subject:</span> {{ $proposal->subject }}</p>
            @endif
            <div class="whitespace-pre-line rounded-xl border border-line bg-card p-4 text-sm leading-relaxed">{{ $proposal->content ?: 'No draft content yet.' }}</div>
            @if (! empty($proposal->ai_metadata['key_points']) && is_array($proposal->ai_metadata['key_points']))
                <ul class="list-disc space-y-1 pl-5 text-sm text-bark">
                    @foreach ($proposal->ai_metadata['key_points'] as $point)
                        <li>{{ $point }}</li>
                    @endforeach
                </ul>
            @endif
            <div class="flex flex-wrap gap-3">
                <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ route('proposals.edit', $proposal) }}">Edit</a>
                @if ($aiEnabled)
                    <form method="POST" action="{{ route('proposals.regenerate', $proposal) }}">
                        @csrf
                        <x-button variant="secondary">Regenerate</x-button>
                    </form>
                @endif
                @if ($proposal->status !== \App\ProposalStatus::Ready)
                    <form method="POST" action="{{ route('proposals.ready', $proposal) }}">
                        @csrf
                        <x-button>Mark ready</x-button>
                    </form>
                @endif
                @if ($proposal->status !== \App\ProposalStatus::Archived)
                    <form method="POST" action="{{ route('proposals.archive', $proposal) }}">
                        @csrf
                        <x-button variant="secondary">Archive</x-button>
                    </form>
                @endif
                <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ route('applications.create', ['opportunity' => $opportunity?->id]) }}">Track application</a>
            </div>
        </section>
    </div>

    <section class="mt-10">
        <h2 class="font-serif text-2xl text-pine">Previous versions</h2>
        <div class="mt-4 space-y-3">
            @forelse ($proposal->versions as $version)
                <article class="rounded-xl border border-line bg-card p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm">
                            <span class="font-medium">{{ $version->source->label() }}</span>
                            <span class="text-bark"> · {{ $version->created_at?->toDayDateTimeString() }}</span>
                        </p>
                        <form method="POST" action="{{ route('proposals.versions.restore', [$proposal, $version]) }}">
                            @csrf
                            <x-button variant="secondary">Restore</x-button>
                        </form>
                    </div>
                    @if ($version->subject)
                        <p class="mt-2 text-sm"><span class="text-bark">Subject:</span> {{ $version->subject }}</p>
                    @endif
                    <div class="mt-2 max-h-40 overflow-auto whitespace-pre-line text-sm text-bark">{{ $version->content }}</div>
                </article>
            @empty
                <p class="text-sm text-bark">No previous versions yet.</p>
            @endforelse
        </div>
    </section>
@endsection
