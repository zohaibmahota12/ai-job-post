@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl text-pine">Notifications</h1>
            <p class="mt-1 text-sm text-bark">{{ $unreadCount }} unread</p>
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <x-button variant="secondary">Mark all as read</x-button>
            </form>
        @endif
    </div>
    <div class="mt-6 space-y-3">
        @forelse ($notifications as $notification)
            @php
                $proposalId = data_get($notification->data, 'proposal_id');
                $applicationId = data_get($notification->data, 'application_id');
                $opportunityId = data_get($notification->data, 'opportunity_id');
            @endphp
            <article class="rounded-xl border border-line bg-card p-4">
                <p class="font-medium">{{ $notification->title }}</p>
                @if ($notification->body)
                    <p class="mt-1 text-sm text-bark">{{ $notification->body }}</p>
                @endif
                <p class="mt-2 text-xs text-bark">{{ $notification->read_at ? 'Read' : 'Unread' }} · {{ $notification->created_at?->diffForHumans() }}</p>
                <div class="mt-3 flex flex-wrap gap-3 text-sm">
                    @if ($proposalId)
                        <a class="underline" href="{{ route('proposals.show', $proposalId) }}">Open proposal</a>
                    @endif
                    @if ($applicationId)
                        <a class="underline" href="{{ route('applications.edit', $applicationId) }}">Open application</a>
                    @endif
                    @if ($opportunityId && ! $proposalId && ! $applicationId)
                        <a class="underline" href="{{ route('opportunities.show', $opportunityId) }}">Open opportunity</a>
                    @endif
                    @if ($notification->read_at === null)
                        <form method="POST" action="{{ route('notifications.update', $notification) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="underline">Mark as read</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <x-empty-state title="No notifications" body="Proposal and application updates for this account will appear here." />
        @endforelse
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
@endsection
