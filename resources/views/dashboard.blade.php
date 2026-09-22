@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="font-serif text-3xl text-pine">Hello, {{ auth()->user()->name }}</h1>
    <p class="mt-2 max-w-2xl text-bark">Matched listings, drafts, applications, and notifications for this account. Nothing here is sent to a client unless you do it yourself.</p>
    @unless ($aiEnabled)
        <p class="mt-3 rounded-md border border-line bg-sand/60 px-3 py-2 text-sm text-bark">AI proposal generation is currently unavailable. You can still create and edit a proposal manually.</p>
    @endunless

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
            'Open opportunities' => $counts['opportunities'],
            'High matches (≥'.$minimumScore.')' => $counts['matches'],
            'Saved' => $counts['saved'],
            'Draft proposals' => $counts['draft_proposals'],
            'Ready proposals' => $counts['ready_proposals'],
            'Applied' => $counts['applied'],
            'Interview' => $counts['interview'],
            'Hired / Rejected' => $counts['hired'].' / '.$counts['rejected'],
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
                @php
                    $opportunity = $match->opportunity;
                    $proposal = $proposalsByOpportunity->get($opportunity->id);
                    $application = $applicationsByOpportunity->get($opportunity->id);
                    $skillReason = data_get($match->reasons, 'factors.skills.reason');
                @endphp
                <a class="block border-b border-line py-3 last:border-0 hover:bg-sand/40" href="{{ route('opportunities.show', $opportunity) }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $opportunity->title }}</p>
                            <p class="mt-1 text-sm text-bark">
                                {{ $opportunity->company ?: 'Client not listed' }}
                                · {{ $opportunity->source?->name ?? 'Unknown source' }}
                            </p>
                            @if ($skillReason)
                                <p class="mt-1 text-sm text-bark">{{ $skillReason }}</p>
                            @endif
                            <p class="mt-1 text-sm text-bark">
                                Proposal: {{ $proposal?->status->label() ?? 'None' }}
                                · App: {{ $application?->status->label() ?? 'Not tracking' }}
                            </p>
                        </div>
                        <p class="shrink-0 font-serif text-2xl text-pine">{{ (int) $match->score }}</p>
                    </div>
                </a>
            @empty
                <p class="text-sm text-bark">No scored matches at or above {{ $minimumScore }} yet. Complete your profile and skills, then collect listings.</p>
            @endforelse
        </x-panel>
        <x-panel title="Proposal work">
            <p class="mb-2 text-xs uppercase tracking-wide text-bark">Drafts</p>
            @forelse ($draftProposals as $proposal)
                <a class="block py-2 text-sm hover:underline" href="{{ route('proposals.show', $proposal) }}">{{ $proposal->opportunity->title }}</a>
            @empty
                <p class="mb-3 text-sm text-bark">No draft proposals.</p>
            @endforelse
            <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-bark">Ready</p>
            @forelse ($readyProposals as $proposal)
                <a class="block py-2 text-sm hover:underline" href="{{ route('proposals.show', $proposal) }}">{{ $proposal->opportunity->title }}</a>
            @empty
                <p class="mb-3 text-sm text-bark">No ready proposals.</p>
            @endforelse
            <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-bark">Recently generated</p>
            @forelse ($recentGeneratedProposals as $proposal)
                <a class="block py-2 text-sm hover:underline" href="{{ route('proposals.show', $proposal) }}">{{ $proposal->opportunity->title }}</a>
            @empty
                <p class="text-sm text-bark">No AI-assisted drafts yet.</p>
            @endforelse
        </x-panel>
        <x-panel title="Saved">
            @forelse ($saved as $item)
                <a class="block py-2 text-sm hover:underline" href="{{ route('opportunities.show', $item->opportunity) }}">{{ $item->opportunity->title }}</a>
            @empty
                <p class="text-sm text-bark">Save a listing when you want to come back to it.</p>
            @endforelse
        </x-panel>
        <x-panel title="Applications">
            @forelse ($applications as $application)
                <a class="block py-2 text-sm hover:underline" href="{{ route('applications.edit', $application) }}">{{ $application->opportunity->title }} · {{ $application->status->label() }}</a>
            @empty
                <p class="text-sm text-bark">Track status after you apply. The app will not submit an application for you.</p>
            @endforelse
        </x-panel>
        <x-panel title="Unread notifications">
            @forelse ($notifications as $notification)
                <a class="block py-2 text-sm hover:underline" href="{{ route('notifications.index') }}">{{ $notification->title }}</a>
            @empty
                <p class="text-sm text-bark">No unread notifications.</p>
            @endforelse
        </x-panel>
    </div>
@endsection
