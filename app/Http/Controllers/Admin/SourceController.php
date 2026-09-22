<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Source;
use App\Models\SourceRun;
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
}
