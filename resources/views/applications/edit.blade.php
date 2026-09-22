@extends('layouts.app')

@section('title', 'Update application')

@section('content')
    <p class="text-sm text-bark">{{ $application->opportunity->title }}</p>
    <h1 class="font-serif text-3xl text-pine">Update application</h1>
    <form method="POST" action="{{ route('applications.update', $application) }}" class="mt-6 max-w-3xl space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label for="status" class="block text-sm font-medium text-bark">Status</label>
            <select id="status" name="status" class="mt-1 w-full rounded-md border border-line bg-white px-3 py-2">
                @foreach (\App\ApplicationStatus::options() as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $application->status->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <x-field label="Notes" name="notes" type="textarea">{{ old('notes', $application->notes) }}</x-field>
        <x-button>Save status</x-button>
    </form>
@endsection
