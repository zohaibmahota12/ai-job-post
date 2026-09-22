@extends('layouts.app')

@section('title', $opportunity->title)

@section('content')
    <p class="text-sm text-bark">{{ $opportunity->source?->name ?? 'Unknown source' }}</p>
    <h1 class="mt-1 font-serif text-3xl text-pine">{{ $opportunity->title }}</h1>
    <p class="mt-2 text-bark">{{ $opportunity->company ?: 'Client not listed' }}</p>
    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
        <div><dt class="text-bark">Job type</dt><dd>{{ $opportunity->job_type?->label() ?? 'Unknown' }}</dd></div>
        <div><dt class="text-bark">Workplace</dt><dd>{{ $opportunity->workplace?->label() ?? 'Unknown' }}</dd></div>
        <div><dt class="text-bark">Location</dt><dd>{{ $opportunity->location ?: 'Not listed' }}</dd></div>
        <div><dt class="text-bark">Budget</dt><dd>{{ $opportunity->budget_min || $opportunity->budget_max ? trim($opportunity->budget_min.'–'.$opportunity->budget_max.' '.$opportunity->currency) : 'Not listed' }}</dd></div>
    </dl>
    @if ($opportunity->skills->isNotEmpty())
        <ul class="mt-4 flex flex-wrap gap-2">
            @foreach ($opportunity->skills as $skill)
                <li class="rounded-full bg-sand px-3 py-1 text-sm">{{ $skill->name }}</li>
            @endforeach
        </ul>
    @endif
    @if ($opportunity->description)
        <div class="mt-6 max-w-3xl whitespace-pre-line text-sm leading-relaxed">{{ $opportunity->description }}</div>
    @endif
    @if ($url = $opportunity->safeSourceUrl())
        <p class="mt-4"><a class="text-sm underline" href="{{ $url }}" rel="noopener noreferrer" target="_blank">Open original listing</a></p>
    @endif

    <div class="mt-6 flex flex-wrap gap-3">
        @if ($saved)
            <form method="POST" action="{{ route('saved.destroy', $saved) }}">
                @csrf
                @method('DELETE')
                <x-button variant="secondary">Unsave</x-button>
            </form>
        @else
            <form method="POST" action="{{ route('opportunities.save', $opportunity) }}">
                @csrf
                <x-button>Save</x-button>
            </form>
        @endif
        @if ($proposal)
            <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ route('proposals.edit', $proposal) }}">Open proposal draft</a>
        @else
            <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ route('proposals.create', ['opportunity' => $opportunity->id]) }}">Write a proposal</a>
        @endif
        @if ($application)
            <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ route('applications.edit', $application) }}">Update application</a>
        @else
            <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ route('applications.create', ['opportunity' => $opportunity->id]) }}">Track an application</a>
        @endif
    </div>

    <section class="mt-8">
        <h2 class="font-serif text-2xl text-pine">Fit notes</h2>
        <p class="mt-1 text-sm text-bark">These checks compare this listing with your profile. They are not a score.</p>
        <ul class="mt-4 space-y-2">
            @foreach ($evaluation->results as $result)
                <li class="rounded-lg border border-line bg-card px-4 py-3 text-sm">
                    <span class="font-medium">{{ $result->label }}</span>
                    <span class="text-bark"> · {{ $result->outcome->label() }}</span>
                    <p class="mt-1 text-bark">{{ $result->detail }}</p>
                </li>
            @endforeach
        </ul>
    </section>
@endsection
