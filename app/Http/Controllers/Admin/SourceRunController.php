<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SourceRun;
use Illuminate\View\View;

class SourceRunController extends Controller
{
    public function index(): View
    {
        $runs = SourceRun::query()
            ->with('source')
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.source-runs.index', [
            'runs' => $runs,
        ]);
    }
}
