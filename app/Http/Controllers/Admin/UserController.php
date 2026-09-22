<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->withCount(['skills', 'proposals', 'applications'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()?->is($user) ?? false, 403, 'You cannot change your own account here.');

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->forceFill([
            'is_active' => $request->boolean('is_active'),
        ])->save();

        $message = $user->is_active ? 'User enabled.' : 'User disabled.';

        return back()->with('status', $message);
    }
}
