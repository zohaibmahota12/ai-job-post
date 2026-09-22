@extends('layouts.app')

@section('title', 'Edit proposal')

@section('content')
    <p class="text-sm text-bark">{{ $proposal->opportunity->title }}</p>
    <h1 class="font-serif text-3xl text-pine">Edit proposal</h1>
    @unless ($aiEnabled)
        <p class="mt-4 rounded-md border border-line bg-sand/60 px-3 py-2 text-sm text-bark">AI proposal generation is currently unavailable. You can still create and edit a proposal manually.</p>
    @endunless
    <form method="POST" action="{{ route('proposals.update', $proposal) }}" class="mt-6 max-w-3xl space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label for="status" class="block text-sm font-medium text-bark">Status</label>
            <select id="status" name="status" class="mt-1 w-full rounded-md border border-line bg-white px-3 py-2">
                @foreach (\App\ProposalStatus::options() as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $proposal->status->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-sm text-bark">Ready is only set when you choose it. AI generation always leaves drafts as draft.</p>
        </div>
        <x-field label="Subject" name="subject" value="{{ old('subject', $proposal->subject) }}" />
        <x-field label="Draft" name="content" type="textarea">{{ old('content', $proposal->content) }}</x-field>
        @if ($proposal->ai_provider || $proposal->ai_model)
            <p class="text-sm text-bark">Last AI assist: {{ $proposal->ai_provider }}{{ $proposal->ai_model ? ' · '.$proposal->ai_model : '' }}</p>
        @endif
        <div class="flex flex-wrap gap-3">
            <x-button>Save draft</x-button>
            <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ route('proposals.show', $proposal) }}">Review</a>
        </div>
    </form>
    <div class="mt-4 flex flex-wrap gap-3">
        @if ($aiEnabled)
            <form method="POST" action="{{ route('proposals.regenerate', $proposal) }}">
                @csrf
                <x-button variant="secondary">Regenerate with AI</x-button>
            </form>
        @endif
        <form method="POST" action="{{ route('proposals.destroy', $proposal) }}">
            @csrf
            @method('DELETE')
            <x-button variant="secondary">Delete draft</x-button>
        </form>
    </div>
@endsection
