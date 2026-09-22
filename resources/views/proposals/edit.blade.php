@extends('layouts.app')

@section('title', 'Edit proposal')

@section('content')
    <p class="text-sm text-bark">{{ $proposal->opportunity->title }}</p>
    <h1 class="font-serif text-3xl text-pine">Edit proposal</h1>
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
        </div>
        <x-field label="Draft" name="content" type="textarea">{{ old('content', $proposal->content) }}</x-field>
        <p class="text-sm text-bark">Provider and model metadata stay empty until proposal generation exists.</p>
        <x-button>Update draft</x-button>
    </form>
    <form method="POST" action="{{ route('proposals.destroy', $proposal) }}" class="mt-4">
        @csrf
        @method('DELETE')
        <x-button variant="secondary">Delete draft</x-button>
    </form>
@endsection
