@extends('layouts.app')

@section('title', 'Applications')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl font-bold tracking-tight text-pine">Applications</h1>
            <p class="mt-2 text-sm text-bark">Update these statuses yourself after you apply, interview, or hear back.</p>
        </div>
        <a class="inline-flex items-center rounded-lg bg-moss px-4 py-2.5 text-sm font-medium text-white transition hover:bg-moss/90" href="{{ route('applications.create') }}">Track one</a>
    </div>
    <div class="mt-6 space-y-2">
        @forelse ($applications as $application)
            <a class="block rounded-2xl border border-line bg-card p-4 shadow-sm shadow-ink/5 transition hover:border-moss/40 hover:shadow-md hover:shadow-ink/5" href="{{ route('applications.edit', $application) }}">
                <p class="font-semibold text-ink">{{ $application->opportunity->title }}</p>
                <p class="mt-1 text-sm text-bark">{{ $application->status->label() }}</p>
            </a>
        @empty
            <x-empty-state title="No applications tracked" body="When you decide to apply, record it here. The status starts at New and only changes when you change it." />
        @endforelse
    </div>
    <div class="mt-6">{{ $applications->links() }}</div>
@endsection
