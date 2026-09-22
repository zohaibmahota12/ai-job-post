@extends('layouts.app')

@section('title', 'New proposal')

@section('content')
    <h1 class="font-serif text-3xl text-pine">New proposal draft</h1>
    <form method="POST" action="{{ route('proposals.store') }}" class="mt-6 max-w-3xl space-y-4">
        @csrf
        <div>
            <label for="opportunity_id" class="block text-sm font-medium text-bark">Opportunity</label>
            <select id="opportunity_id" name="opportunity_id" class="mt-1 w-full rounded-md border border-line bg-white px-3 py-2" required>
                <option value="">Choose a listing</option>
                @foreach ($opportunities as $item)
                    <option value="{{ $item->id }}" @selected((int) old('opportunity_id', $opportunity?->id) === $item->id)>{{ $item->title }}</option>
                @endforeach
            </select>
            @error('opportunity_id')
                <p class="mt-1 text-sm text-clay">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-bark">Status</label>
            <select id="status" name="status" class="mt-1 w-full rounded-md border border-line bg-white px-3 py-2">
                @foreach (\App\ProposalStatus::options() as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <x-field label="Draft" name="content" type="textarea">{{ old('content') }}</x-field>
        <x-button>Save draft</x-button>
    </form>
@endsection
