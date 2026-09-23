<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSourceRequest;
use App\Models\Source;
use App\Models\SourceRun;
use App\Services\Opportunities\OpportunityCollector;
use App\SourceRunStatus;
use App\Support\SecretRedactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class SourceController extends Controller
{
    public function index(): View
    {
        $sources = Source::query()
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $latestRuns = SourceRun::query()
            ->whereIn('source_id', $sources->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->unique('source_id')
            ->keyBy('source_id');

        return view('admin.sources.index', [
            'sources' => $sources,
            'latestRuns' => $latestRuns,
        ]);
    }

    public function update(UpdateSourceRequest $request, Source $source): RedirectResponse
    {
        $source->forceFill([
            'is_enabled' => $request->boolean('is_enabled'),
        ])->save();

        return redirect()
            ->route('admin.sources.index')
            ->with('status', $source->name.' is now '.($source->is_enabled ? 'enabled' : 'disabled').'.');
    }

    public function collect(Source $source, OpportunityCollector $collector, SecretRedactor $redactor): RedirectResponse
    {
        if (! $source->is_enabled) {
            return redirect()
                ->route('admin.sources.index')
                ->with('status', $source->name.' is disabled. Enable it before running collection.');
        }

        try {
            $run = $collector->collect($source->key)->first();
        } catch (RuntimeException|Throwable $exception) {
            return redirect()
                ->route('admin.sources.index')
                ->with('status', $redactor->redact($exception->getMessage()) ?? 'Collection failed.');
        }

        if (! $run instanceof SourceRun) {
            return redirect()
                ->route('admin.sources.index')
                ->with('status', 'No collection run was recorded for '.$source->name.'.');
        }

        if ($run->status === SourceRunStatus::Failed) {
            return redirect()
                ->route('admin.sources.index')
                ->with('status', $source->name.' collection failed: '.($redactor->redact($run->error_message) ?? 'unknown error'));
        }

        return redirect()
            ->route('admin.sources.index')
            ->with(
                'status',
                sprintf(
                    '%s collection %s (found %d, created %d, updated %d, duplicated %d).',
                    $source->name,
                    $run->status->value,
                    $run->items_found,
                    $run->items_created,
                    $run->items_updated,
                    $run->items_duplicated,
                ),
            );
    }
}
