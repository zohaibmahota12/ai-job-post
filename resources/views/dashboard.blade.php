@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="font-serif text-3xl font-bold tracking-tight text-pine">Hello, {{ auth()->user()->name }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-bark sm:text-base">Matched listings, drafts, and applications for this account. Nothing is sent unless you do it.</p>
        </div>
        <a href="{{ route('opportunities.index') }}" class="inline-flex items-center justify-center rounded-lg bg-moss px-4 py-2.5 text-sm font-medium text-white transition hover:bg-moss/90">Browse opportunities</a>
    </div>

    @unless ($aiEnabled)
        <p class="mt-4 rounded-xl border border-line bg-sand px-4 py-3 text-sm text-bark">AI proposal generation is currently unavailable. You can still create and edit a proposal manually.</p>
    @endunless

    <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Open opportunities', $counts['opportunities'], route('opportunities.index')],
            ['High matches (≥'.$minimumScore.')', $counts['matches'], route('opportunities.index', ['min_score' => $minimumScore])],
            ['Draft proposals', $counts['draft_proposals'], route('proposals.index')],
            ['Unread notifications', $counts['notifications'], route('notifications.index')],
        ] as [$label, $count, $href])
            <a href="{{ $href }}" class="rounded-2xl border border-line bg-card px-4 py-4 shadow-sm shadow-ink/5 transition hover:border-moss/40 hover:shadow-md hover:shadow-ink/5">
                <p class="text-sm text-bark">{{ $label }}</p>
                <p class="mt-1 font-serif text-3xl font-bold tracking-tight text-pine">{{ $count }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-bark">
        <span>Saved <strong class="font-semibold text-ink">{{ $counts['saved'] }}</strong></span>
        <span>Ready <strong class="font-semibold text-ink">{{ $counts['ready_proposals'] }}</strong></span>
        <span>Applied <strong class="font-semibold text-ink">{{ $counts['applied'] }}</strong></span>
        <span>Interview <strong class="font-semibold text-ink">{{ $counts['interview'] }}</strong></span>
        <span>Hired / Rejected <strong class="font-semibold text-ink">{{ $counts['hired'] }} / {{ $counts['rejected'] }}</strong></span>
    </div>

    <div class="mt-8 grid gap-5 lg:grid-cols-2">
        <x-panel title="High-match opportunities">
            @forelse ($matches as $match)
                @php
                    $opportunity = $match->opportunity;
                    $proposal = $proposalsByOpportunity->get($opportunity->id);
                    $application = $applicationsByOpportunity->get($opportunity->id);
                    $skillReason = data_get($match->reasons, 'factors.skills.reason');
                @endphp
                <a class="block -mx-2 rounded-xl px-2 py-3 transition hover:bg-sand/80" href="{{ route('opportunities.show', $opportunity) }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-ink">{{ $opportunity->title }}</p>
                            <p class="mt-1 text-sm text-bark">
                                {{ $opportunity->company ?: 'Client not listed' }}
                                · {{ $opportunity->source?->name ?? 'Unknown source' }}
                            </p>
                            @if ($skillReason)
                                <p class="mt-1 text-sm text-bark">{{ $skillReason }}</p>
                            @endif
                            <p class="mt-1 text-xs text-bark">
                                Proposal: {{ $proposal?->status->label() ?? 'None' }}
                                · App: {{ $application?->status->label() ?? 'Not tracking' }}
                            </p>
                        </div>
                        <p class="shrink-0 font-serif text-2xl font-bold text-moss">{{ (int) $match->score }}</p>
                    </div>
                </a>
            @empty
                <p class="text-sm text-bark">No scored matches at or above {{ $minimumScore }} yet. Complete your profile and skills, then collect listings.</p>
            @endforelse
        </x-panel>

        <x-panel title="New opportunities">
            @forelse ($opportunities as $opportunity)
                <a class="block -mx-2 rounded-xl px-2 py-2.5 text-sm transition hover:bg-sand/80" href="{{ route('opportunities.show', $opportunity) }}">
                    <span class="font-medium text-ink">{{ $opportunity->title }}</span>
                    <span class="text-bark"> · {{ $opportunity->company ?: 'Client not listed' }}</span>
                </a>
            @empty
                <p class="text-sm text-bark">No listings have been collected yet. An admin can enable a configured RSS or JSON source and run <code class="rounded bg-sand px-1 text-xs">php artisan opportunities:collect</code>.</p>
            @endforelse
        </x-panel>

        <x-panel title="Proposal work">
            <p class="mb-2 text-[11px] font-semibold tracking-wider text-bark uppercase">Drafts</p>
            @forelse ($draftProposals as $proposal)
                <a class="block -mx-2 rounded-xl px-2 py-2 text-sm transition hover:bg-sand/80" href="{{ route('proposals.show', $proposal) }}">{{ $proposal->opportunity->title }}</a>
            @empty
                <p class="mb-3 text-sm text-bark">No draft proposals.</p>
            @endforelse
            <p class="mb-2 mt-4 text-[11px] font-semibold tracking-wider text-bark uppercase">Ready</p>
            @forelse ($readyProposals as $proposal)
                <a class="block -mx-2 rounded-xl px-2 py-2 text-sm transition hover:bg-sand/80" href="{{ route('proposals.show', $proposal) }}">{{ $proposal->opportunity->title }}</a>
            @empty
                <p class="mb-3 text-sm text-bark">No ready proposals.</p>
            @endforelse
            <p class="mb-2 mt-4 text-[11px] font-semibold tracking-wider text-bark uppercase">Recently generated</p>
            @forelse ($recentGeneratedProposals as $proposal)
                <a class="block -mx-2 rounded-xl px-2 py-2 text-sm transition hover:bg-sand/80" href="{{ route('proposals.show', $proposal) }}">{{ $proposal->opportunity->title }}</a>
            @empty
                <p class="text-sm text-bark">No AI-assisted drafts yet.</p>
            @endforelse
        </x-panel>

        <div class="grid gap-5">
            <x-panel title="Saved">
                @forelse ($saved as $item)
                    <a class="block -mx-2 rounded-xl px-2 py-2 text-sm transition hover:bg-sand/80" href="{{ route('opportunities.show', $item->opportunity) }}">{{ $item->opportunity->title }}</a>
                @empty
                    <p class="text-sm text-bark">Save a listing when you want to come back to it.</p>
                @endforelse
            </x-panel>
            <x-panel title="Applications">
                @forelse ($applications as $application)
                    <a class="block -mx-2 rounded-xl px-2 py-2 text-sm transition hover:bg-sand/80" href="{{ route('applications.edit', $application) }}">{{ $application->opportunity->title }} · {{ $application->status->label() }}</a>
                @empty
                    <p class="text-sm text-bark">Track status after you apply. The app will not submit an application for you.</p>
                @endforelse
            </x-panel>
            <x-panel title="Unread notifications">
                @forelse ($notifications as $notification)
                    <a class="block -mx-2 rounded-xl px-2 py-2 text-sm transition hover:bg-sand/80" href="{{ route('notifications.index') }}">{{ $notification->title }}</a>
                @empty
                    <p class="text-sm text-bark">No unread notifications.</p>
                @endforelse
            </x-panel>
        </div>
    </div>
@endsection
