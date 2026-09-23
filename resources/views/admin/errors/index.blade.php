@extends('layouts.app')

@section('title', 'System errors')

@section('content')
    <h1 class="font-serif text-3xl font-bold tracking-tight text-pine">System errors</h1>
    <div class="mt-6 space-y-3">
        @forelse ($errors as $error)
            <article class="rounded-2xl border border-line bg-card shadow-sm shadow-ink/5 p-4">
                <p class="text-xs uppercase tracking-wide text-bark">{{ $error->level }} · {{ $error->occurred_at?->toDayDateTimeString() }}</p>
                <p class="mt-2 text-sm">{{ $error->message }}</p>
                <p class="mt-1 text-xs text-bark">{{ $error->sourceRun?->source?->name }}</p>
            </article>
        @empty
            <x-empty-state title="No recorded errors" body="Failed collection runs write a row here so shared hosting does not depend on digging through log files." />
        @endforelse
    </div>
    <div class="mt-6">{{ $errors->links() }}</div>
@endsection