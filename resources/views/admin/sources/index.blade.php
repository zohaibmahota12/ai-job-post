@extends('layouts.app')

@section('title', 'Sources')

@section('content')
    <h1 class="font-serif text-3xl text-pine">Sources</h1>
    <p class="mt-2 max-w-2xl text-sm text-bark">Adapters plug into <code class="text-xs">php artisan opportunities:collect</code>. Enable only sources with a configured public endpoint. Agent Reach remains an optional external boundary, not the core pipeline.</p>
    <div class="mt-6 space-y-3">
        @forelse ($sources as $source)
            @php
                $run = $latestRuns->get($source->id);
                $health = $source->health();
            @endphp
            <article class="rounded-xl border border-line bg-card p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium">{{ $source->name }}</p>
                            <x-source-health :health="$health" />
                        </div>
                        <p class="mt-1 text-sm text-bark">{{ $source->key }} · {{ $source->driver }} · {{ $source->type }} · {{ $source->is_enabled ? 'Enabled' : 'Disabled' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.sources.update', $source) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_enabled" value="{{ $source->is_enabled ? 0 : 1 }}">
                            <x-button variant="secondary">{{ $source->is_enabled ? 'Disable' : 'Enable' }}</x-button>
                        </form>
                        @if ($source->is_enabled)
                            <form method="POST" action="{{ route('admin.sources.collect', $source) }}">
                                @csrf
                                <x-button variant="secondary">Run now</x-button>
                            </form>
                        @endif
                    </div>
                </div>
                @if ($source->description)
                    <p class="mt-2 text-sm">{{ $source->description }}</p>
                @endif
                @if ($source->safeConfig() !== [])
                    <p class="mt-2 text-sm text-bark">Config: {{ collect($source->safeConfig())->reject(fn ($value, $key) => in_array($key, ['field_map'], true))->map(fn ($value, $key) => $key.'='.(is_scalar($value) || $value === null ? (string) $value : '[complex]'))->implode(', ') }}</p>
                @endif
                <p class="mt-2 text-sm text-bark">Latest run: {{ $run?->status->label() ?? 'None' }}</p>
                <p class="mt-1 text-sm text-bark">Last run: {{ $source->last_run_at?->toDayDateTimeString() ?? 'Never' }} · Last success: {{ $source->last_success_at?->toDayDateTimeString() ?? 'Never' }}</p>
                <p class="mt-1 text-sm text-bark">
                    Consecutive failures: {{ $source->consecutive_failures }}
                    · Last items: {{ $source->last_item_count ?? '—' }}
                    · Created: {{ $source->last_created_count ?? '—' }}
                    · Updated: {{ $source->last_updated_count ?? '—' }}
                    · Duplicated: {{ $source->last_duplicated_count ?? '—' }}
                    · Duration: {{ $source->last_duration_ms !== null ? $source->last_duration_ms.' ms' : '—' }}
                </p>
                @if ($source->last_error)
                    <p class="mt-2 text-sm text-red-800">Last error: {{ $source->last_error }}</p>
                @endif
            </article>
        @empty
            <x-empty-state title="No sources" body="Run the database seeder to register source adapters." />
        @endforelse
    </div>
@endsection
