@extends('layouts.app')

@section('title', 'Proposals')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <h1 class="font-serif text-3xl text-pine">Proposal drafts</h1>
        <a class="text-sm underline" href="{{ route('proposals.create') }}">New draft</a>
    </div>
    <p class="mt-2 text-sm text-bark">
        @if ($aiEnabled)
            Generate with AI or write manually. Drafts are never sent automatically.
        @else
            AI proposal generation is currently unavailable. You can still create and edit a proposal manually.
        @endif
    </p>
    <div class="mt-6 space-y-3">
        @forelse ($proposals as $proposal)
            <a class="block rounded-xl border border-line bg-card p-4" href="{{ route('proposals.show', $proposal) }}">
                <p class="font-medium">{{ $proposal->opportunity->title }}</p>
                <p class="mt-1 text-sm text-bark">
                    {{ $proposal->status->label() }}
                    · {{ $proposal->generated_by_ai ? 'AI assisted' : 'Manual' }}
                </p>
            </a>
        @empty
            <x-empty-state title="No drafts yet" body="Open an opportunity and start a draft when you are ready to write." />
        @endforelse
    </div>
    <div class="mt-6">{{ $proposals->links() }}</div>
@endsection
