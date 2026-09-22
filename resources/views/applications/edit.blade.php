@extends('layouts.app')

@section('title', 'Update application')

@section('content')
    @php($external = $application->opportunity->safeSourceUrl())
    <p class="text-sm text-bark">{{ $application->opportunity->title }}</p>
    <h1 class="font-serif text-3xl text-pine">Update application</h1>
    <p class="mt-2 text-sm text-bark">Track status after you apply yourself. Opportunity Hunter does not submit applications.</p>

    <div class="mt-4 flex flex-wrap gap-3">
        @if ($external)
            <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ $external }}" rel="noopener noreferrer" target="_blank">Apply externally</a>
        @endif
        @if ($application->status !== \App\ApplicationStatus::Applied)
            <form method="POST" action="{{ route('applications.mark-applied', $application) }}">
                @csrf
                <x-button>Mark as applied</x-button>
            </form>
        @endif
        @if ($application->proposal)
            <a class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm" href="{{ route('proposals.show', $application->proposal) }}">View proposal</a>
        @endif
    </div>

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
        <x-field label="Contact name" name="contact_name" value="{{ old('contact_name', $application->contact_name) }}" />
        <x-field label="Follow-up date" name="follow_up_at" type="date" value="{{ old('follow_up_at', optional($application->follow_up_at)->format('Y-m-d')) }}" />
        <x-field label="External application URL" name="external_url" type="url" value="{{ old('external_url', $application->external_url) }}" />
        <x-field label="Notes" name="notes" type="textarea">{{ old('notes', $application->notes) }}</x-field>
        <x-button>Save status</x-button>
    </form>

    <section class="mt-10 max-w-3xl">
        <h2 class="font-serif text-2xl text-pine">Status history</h2>
        <ul class="mt-4 space-y-2">
            @forelse ($application->statusHistories as $history)
                <li class="rounded-lg border border-line bg-card px-4 py-3 text-sm">
                    <p>
                        {{ $history->old_status?->label() ?? '—' }}
                        →
                        {{ $history->new_status->label() }}
                    </p>
                    <p class="mt-1 text-bark">
                        {{ $history->created_at?->toDayDateTimeString() }}
                        @if ($history->changedBy)
                            · {{ $history->changedBy->name }}
                        @endif
                    </p>
                    @if ($history->note)
                        <p class="mt-1 text-bark">{{ $history->note }}</p>
                    @endif
                </li>
            @empty
                <li class="text-sm text-bark">No status changes recorded yet.</li>
            @endforelse
        </ul>
    </section>
@endsection
