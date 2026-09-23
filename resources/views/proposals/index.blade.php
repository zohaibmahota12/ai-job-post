@extends('layouts.app')

@section('title', 'Proposals')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl font-bold tracking-tight text-pine">Proposal drafts</h1>
            <p class="mt-2 text-sm text-bark">
                @if ($aiEnabled)
                    Generate with AI or write manually. Drafts are never sent automatically.
                @else
                    AI proposal generation is currently unavailable. You can still create and edit a proposal manually.
                @endif
            </p>
        </div>
        <a class="inline-flex items-center rounded-lg bg-moss px-4 py-2.5 text-sm font-medium text-white transition hover:bg-moss/90" href="{{ route('proposals.create') }}">New draft</a>
    </div>
    <div class="mt-6 space-y-2">
        @forelse ($proposals as $proposal)
            <a class="block rounded-2xl border border-line bg-card p-4 shadow-sm shadow-ink/5 transition hover:border-moss/40 hover:shadow-md hover:shadow-ink/5" href="{{ route('proposals.show', $proposal) }}">
                <p class="font-semibold text-ink">{{ $proposal->opportunity->title }}</p>
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
