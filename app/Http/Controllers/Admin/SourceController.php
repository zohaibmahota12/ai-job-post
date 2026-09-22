<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSourceRequest;
use App\Models\Source;
use App\Models\SourceRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
}
