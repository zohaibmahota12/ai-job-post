@extends('layouts.app')

@section('title', 'Opportunities')

@section('content')
    <h1 class="font-serif text-3xl text-pine">Opportunities</h1>
    <p class="mt-2 text-sm text-bark">Public listings collected into one list. Saving, drafts, and applications stay on your account.</p>
    <div class="mt-6 space-y-3">
        @forelse ($opportunities as $opportunity)
            <a href="{{ route('opportunities.show', $opportunity) }}" class="block rounded-xl border border-line bg-card p-4 hover:border-moss">
                <p class="font-medium">{{ $opportunity->title }}</p>
                <p class="mt-1 text-sm text-bark">{{ $opportunity->company ?: 'Client not listed' }} · {{ $opportunity->job_type?->label() ?? 'Type unknown' }} · {{ $opportunity->workplace?->label() ?? 'Workplace unknown' }}</p>
            </a>
        @empty
            <x-empty-state title="Nothing collected yet" body="The Agent Reach source is installed as a disabled adapter. Phase 1 does not fetch live listings." />
        @endforelse
    </div>
    <div class="mt-6">{{ $opportunities->links() }}</div>
@endsection
