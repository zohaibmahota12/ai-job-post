@extends('layouts.app')

@section('title', 'Sources')

@section('content')
    <h1 class="font-serif text-3xl text-pine">Sources</h1>
    <p class="mt-2 max-w-2xl text-sm text-bark">Adapters plug into one collection command. Agent Reach is registered and disabled. Enabling it would only record a failed run until Phase 2 implements a real call.</p>
    <div class="mt-6 space-y-3">
        @forelse ($sources as $source)
            @php $run = $latestRuns->get($source->id); @endphp
            <article class="rounded-xl border border-line bg-card p-4">
                <p class="font-medium">{{ $source->name }}</p>
                <p class="mt-1 text-sm text-bark">{{ $source->driver }} · {{ $source->is_enabled ? 'Enabled' : 'Disabled' }}</p>
                @if ($source->description)
                    <p class="mt-2 text-sm">{{ $source->description }}</p>
                @endif
                <p class="mt-2 text-sm text-bark">Latest run: {{ $run?->status->label() ?? 'None' }}</p>
            </article>
        @empty
            <x-empty-state title="No sources" body="Run the database seeder to register the Agent Reach source." />
        @endforelse
    </div>
@endsection
