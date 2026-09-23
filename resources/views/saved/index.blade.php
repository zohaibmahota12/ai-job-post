@extends('layouts.app')

@section('title', 'Saved opportunities')

@section('content')
    <h1 class="font-serif text-3xl font-bold tracking-tight text-pine">Saved opportunities</h1>
    <p class="mt-2 text-sm text-bark">Listings you marked to revisit.</p>
    <div class="mt-6 space-y-2">
        @forelse ($savedOpportunities as $saved)
            <div class="flex flex-col gap-3 rounded-2xl border border-line bg-card p-4 shadow-sm shadow-ink/5 sm:flex-row sm:items-center sm:justify-between">
                <a class="font-semibold text-ink transition hover:text-moss" href="{{ route('opportunities.show', $saved->opportunity) }}">{{ $saved->opportunity->title }}</a>
                <form method="POST" action="{{ route('saved.destroy', $saved) }}">
                    @csrf
                    @method('DELETE')
                    <x-button variant="secondary">Remove</x-button>
                </form>
            </div>
        @empty
            <x-empty-state title="No saved listings" body="When a listing is worth a second look, save it from the opportunity page." />
        @endforelse
    </div>
    <div class="mt-6">{{ $savedOpportunities->links() }}</div>
@endsection
