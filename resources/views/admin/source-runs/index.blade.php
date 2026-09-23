@extends('layouts.app')

@section('title', 'Collection runs')

@section('content')
    <h1 class="font-serif text-3xl text-pine">Collection runs</h1>
    <div class="mt-6 overflow-x-auto rounded-xl border border-line bg-card">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-line text-bark">
                <tr>
                    <th class="px-4 py-3 font-medium">Source</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Started</th>
                    <th class="px-4 py-3 font-medium">Finished</th>
                    <th class="px-4 py-3 font-medium">Duration</th>
                    <th class="px-4 py-3 font-medium">Found</th>
                    <th class="px-4 py-3 font-medium">Created</th>
                    <th class="px-4 py-3 font-medium">Updated</th>
                    <th class="px-4 py-3 font-medium">Skipped</th>
                    <th class="px-4 py-3 font-medium">Duplicates</th>
                    <th class="px-4 py-3 font-medium">Message</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($runs as $run)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-4 py-3">{{ $run->source?->name }}</td>
                        <td class="px-4 py-3">{{ $run->status->label() }}</td>
                        <td class="px-4 py-3">{{ $run->started_at?->toDayDateTimeString() ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $run->finished_at?->toDayDateTimeString() ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $run->durationMs() !== null ? $run->durationMs().' ms' : '—' }}</td>
                        <td class="px-4 py-3">{{ $run->items_found }}</td>
                        <td class="px-4 py-3">{{ $run->items_created }}</td>
                        <td class="px-4 py-3">{{ $run->items_updated }}</td>
                        <td class="px-4 py-3">{{ $run->items_skipped }}</td>
                        <td class="px-4 py-3">{{ $run->items_duplicated }}</td>
                        <td class="px-4 py-3">{{ $run->error_message }}</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-6 text-bark" colspan="11">No collection runs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $runs->links() }}</div>
@endsection
