@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <h1 class="font-serif text-3xl text-pine">Notifications</h1>
    <div class="mt-6 space-y-3">
        @forelse ($notifications as $notification)
            <article class="rounded-xl border border-line bg-card p-4">
                <p class="font-medium">{{ $notification->title }}</p>
                @if ($notification->body)
                    <p class="mt-1 text-sm text-bark">{{ $notification->body }}</p>
                @endif
                <p class="mt-2 text-xs text-bark">{{ $notification->read_at ? 'Read' : 'Unread' }}</p>
                @if ($notification->read_at === null)
                    <form method="POST" action="{{ route('notifications.update', $notification) }}" class="mt-3">
                        @csrf
                        @method('PATCH')
                        <x-button variant="secondary">Mark as read</x-button>
                    </form>
                @endif
            </article>
        @empty
            <x-empty-state title="No notifications" body="Scheduled collection and match alerts will land here in a later phase." />
        @endforelse
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
@endsection
