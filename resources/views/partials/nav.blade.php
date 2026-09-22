@php
    $link = $variant === 'dark'
        ? 'block rounded-md px-3 py-2 text-sm text-sand hover:bg-white/10'
        : 'block rounded-md px-3 py-2 text-sm text-ink hover:bg-sand';
@endphp
<nav class="flex flex-col gap-1 px-3 pb-6">
    <a class="{{ $link }}" href="{{ route('dashboard') }}">Dashboard</a>
    <a class="{{ $link }}" href="{{ route('opportunities.index') }}">Opportunities</a>
    <a class="{{ $link }}" href="{{ route('saved.index') }}">Saved</a>
    <a class="{{ $link }}" href="{{ route('proposals.index') }}">Proposals</a>
    <a class="{{ $link }}" href="{{ route('applications.index') }}">Applications</a>
    <a class="{{ $link }}" href="{{ route('notifications.index') }}">Notifications</a>
    <a class="{{ $link }}" href="{{ route('profile.edit') }}">Profile</a>
    @if (auth()->user()?->isAdmin())
        <p class="mt-4 px-3 text-xs uppercase tracking-wide {{ $variant === 'dark' ? 'text-sand/60' : 'text-bark' }}">Admin</p>
        <a class="{{ $link }}" href="{{ route('admin.users.index') }}">Users</a>
        <a class="{{ $link }}" href="{{ route('admin.sources.index') }}">Sources</a>
        <a class="{{ $link }}" href="{{ route('admin.source-runs.index') }}">Collection runs</a>
        <a class="{{ $link }}" href="{{ route('admin.errors.index') }}">System errors</a>
    @endif
    <form method="POST" action="{{ route('logout') }}" class="mt-4 px-3">
        @csrf
        <button type="submit" class="text-sm underline {{ $variant === 'dark' ? 'text-sand' : 'text-bark' }}">Log out</button>
    </form>
</nav>
