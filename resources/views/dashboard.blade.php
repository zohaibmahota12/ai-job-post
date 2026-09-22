@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="font-serif text-3xl text-pine">Hello, {{ auth()->user()->name }}</h1>
    <p class="mt-2 max-w-2xl text-bark">Matched listings, saved work, drafts, and applications for this account. Nothing here is sent to a client unless you do it yourself.</p>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
            'Open opportunities' => $counts['opportunities'],
            'High matches (≥'.$minimumScore.')' => $counts['matches'],
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
                <a class="block py-2 text-sm hover:underline" href="{{ route('opportunities.show', $opportunity) }}">
                    {{ $opportunity->title }}
                    <span class="text-bark"> · {{ $opportunity->company ?: 'Client not listed' }}</span>
                </a>
            @empty
                <p class="text-sm text-bark">No listings have been collected yet. An admin can enable a configured RSS or JSON source and run <code class="text-xs">php artisan opportunities:collect</code>.</p>
            @endforelse
        </x-panel>
        <x-panel title="High-match opportunities">
            @forelse ($matches as $match)
                @php $opportunity = $match->opportunity; @endphp
                <a class="block border-b border-line py-3 last:border-0 hover:bg-sand/40" href="{{ route('opportunities.show', $opportunity) }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $opportunity->title }}</p>
                            <p class="mt-1 text-sm text-bark">
                                {{ $opportunity->company ?: 'Client not listed' }}
                                · {{ $opportunity->source?->name ?? 'Unknown source' }}
                                · {{ $opportunity->workplace?->label() ?? 'Workplace unknown' }}
                                · {{ $opportunity->job_type?->label() ?? 'Type unknown' }}
                            </p>
                            @if ($opportunity->location)
                                <p class="mt-1 text-sm text-bark">{{ $opportunity->location }}</p>
                            @endif
                        </div>
                        <p class="shrink-0 font-serif text-2xl text-pine">{{ (int) $match->score }}</p>
                    </div>
                </a>
            @empty
                <p class="text-sm text-bark">No scored matches at or above {{ $minimumScore }} yet. Complete your profile and skills, then collect listings.</p>
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
