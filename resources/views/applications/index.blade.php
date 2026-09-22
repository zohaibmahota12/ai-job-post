@extends('layouts.app')

@section('title', 'Applications')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <h1 class="font-serif text-3xl text-pine">Applications</h1>
        <a class="text-sm underline" href="{{ route('applications.create') }}">Track one</a>
    </div>
    <p class="mt-2 text-sm text-bark">Update these statuses yourself after you apply, interview, or hear back.</p>
    <div class="mt-6 space-y-3">
        @forelse ($applications as $application)
            <a class="block rounded-xl border border-line bg-card p-4" href="{{ route('applications.edit', $application) }}">
                <p class="font-medium">{{ $application->opportunity->title }}</p>
                <p class="mt-1 text-sm text-bark">{{ $application->status->label() }}</p>
            </a>
        @empty
            <x-empty-state title="No applications tracked" body="When you decide to apply, record it here. The status starts at New and only changes when you change it." />
        @endforelse
    </div>
    <div class="mt-6">{{ $applications->links() }}</div>
@endsection
