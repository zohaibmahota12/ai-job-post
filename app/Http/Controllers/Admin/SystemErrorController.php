<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemError;
use Illuminate\View\View;

class SystemErrorController extends Controller
{
    public function index(): View
    {
        $errors = SystemError::query()
            ->with('sourceRun.source')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.errors.index', [
            'errors' => $errors,
        ]);
    }
}
