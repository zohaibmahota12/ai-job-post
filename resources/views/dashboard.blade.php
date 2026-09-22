@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="font-serif text-3xl text-pine">Hello, {{ auth()->user()->name }}</h1>
    <p class="mt-2 max-w-2xl text-bark">New listings, saved work, drafts, and applications for this account. Nothing here is sent to a client unless you do it yourself.</p>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
            'Open opportunities' => $counts['opportunities'],
            'Scored matches' => $counts['matches'],
            'Saved' => $counts['saved'],
            'Proposal drafts' => $counts['proposals'],
            'Applications' => $counts['applications'],
            'Unread notifications' => $counts['notifications'],
        ] as $label => $count)
            <div class="rounded-xl border border-line bg-card px-4 py-3">
                <p class="text-sm text-bark">{{ $label }}</p>
                <p class="font-serif text-3xl text-pine">{{ $count }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid gap-4 lg:grid-cols-2">
        <x-panel title="New opportunities">
            @forelse ($opportunities as $opportunity)
                <a class="block py-2 text-sm hover:underline" href="{{ route('opportunities.show', $opportunity) }}">{{ $opportunity->title }}</a>
            @empty
                <p class="text-sm text-bark">No listings have been collected yet. Collection stays off until a source is enabled in a later phase.</p>
            @endforelse
        </x-panel>
        <x-panel title="High-match opportunities">
            @forelse ($matches as $match)
                <a class="block py-2 text-sm hover:underline" href="{{ route('opportunities.show', $match->opportunity) }}">{{ $match->opportunity->title }}</a>
            @empty
                <p class="text-sm text-bark">Scores are not stored yet. Open a listing to see which of your preferences line up. A numeric score comes later.</p>
            @endforelse
        </x-panel>
        <x-panel title="Saved">
            @forelse ($saved as $item)
                <a class="block py-2 text-sm hover:underline" href="{{ route('opportunities.show', $item->opportunity) }}">{{ $item->opportunity->title }}</a>
            @empty
                <p class="text-sm text-bark">Save a listing when you want to come back to it.</p>
            @endforelse
        </x-panel>
        <x-panel title="Proposal drafts">
            @forelse ($proposals as $proposal)
                <a class="block py-2 text-sm hover:underline" href="{{ route('proposals.edit', $proposal) }}">{{ $proposal->opportunity->title }}</a>
            @empty
                <p class="text-sm text-bark">Drafts you write live here. Automatic proposal writing is not part of this phase.</p>
            @endforelse
        </x-panel>
        <x-panel title="Applications">
            @forelse ($applications as $application)
                <a class="block py-2 text-sm hover:underline" href="{{ route('applications.edit', $application) }}">{{ $application->opportunity->title }} · {{ $application->status->label() }}</a>
            @empty
                <p class="text-sm text-bark">Track status after you apply. The app will not submit an application for you.</p>
            @endforelse
        </x-panel>
        <x-panel title="Notifications">
            @forelse ($notifications as $notification)
                <p class="py-2 text-sm">{{ $notification->title }}</p>
            @empty
                <p class="text-sm text-bark">No notifications yet.</p>
            @endforelse
        </x-panel>
    </div>
@endsection
