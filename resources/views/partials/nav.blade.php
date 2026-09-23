@php
    $isDark = ($variant ?? 'light') === 'dark';
    $base = $isDark
        ? 'flex items-center rounded-lg px-3 py-2 text-sm font-medium transition'
        : 'flex items-center rounded-lg px-3 py-2 text-sm font-medium transition';
    $idle = $isDark
        ? 'text-sand/80 hover:bg-white/8 hover:text-white'
        : 'text-bark hover:bg-sand hover:text-ink';
    $active = $isDark
        ? 'bg-white/12 text-white'
        : 'bg-moss/10 text-moss';

    $item = function (string $route, bool $exact = false) use ($base, $idle, $active): string {
        $matched = $exact
            ? request()->routeIs($route)
            : request()->routeIs($route, $route.'.*');

        return $base.' '.($matched ? $active : $idle);
    };
@endphp
<nav class="flex flex-col gap-0.5 px-3 pb-6">
    <a class="{{ $item('dashboard', true) }}" href="{{ route('dashboard') }}">Dashboard</a>
    <a class="{{ $item('opportunities') }}" href="{{ route('opportunities.index') }}">Opportunities</a>
    <a class="{{ $item('saved') }}" href="{{ route('saved.index') }}">Saved</a>
    <a class="{{ $item('proposals') }}" href="{{ route('proposals.index') }}">Proposals</a>
    <a class="{{ $item('applications') }}" href="{{ route('applications.index') }}">Applications</a>
    <a class="{{ $item('notifications') }}" href="{{ route('notifications.index') }}">
        Notifications
        @php($unread = auth()->user()?->userNotifications()->whereNull('read_at')->count() ?? 0)
        @if ($unread > 0)
            <span class="ml-auto inline-flex min-w-5 items-center justify-center rounded-md bg-moss px-1.5 text-xs font-semibold text-white">{{ $unread }}</span>
        @endif
    </a>
    <a class="{{ $item('profile') }}" href="{{ route('profile.edit') }}">Profile</a>
    @if (auth()->user()?->isAdmin())
        <p class="mt-5 px-3 text-[11px] font-semibold tracking-wider text-bark uppercase {{ $isDark ? 'text-sand/45' : '' }}">Admin</p>
        <a class="{{ $item('admin.users') }}" href="{{ route('admin.users.index') }}">Users</a>
        <a class="{{ $item('admin.sources') }}" href="{{ route('admin.sources.index') }}">Sources</a>
        <a class="{{ $item('admin.source-runs') }}" href="{{ route('admin.source-runs.index') }}">Collection runs</a>
        <a class="{{ $item('admin.errors') }}" href="{{ route('admin.errors.index') }}">System errors</a>
    @endif
    <form method="POST" action="{{ route('logout') }}" class="mt-5 px-3">
        @csrf
        <button type="submit" class="text-sm font-medium transition {{ $isDark ? 'text-sand/70 hover:text-white' : 'text-bark hover:text-ink' }}">Log out</button>
    </form>
</nav>
