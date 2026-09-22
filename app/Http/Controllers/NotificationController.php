<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $notifications = UserNotification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function update(UserNotification $notification): RedirectResponse
    {
        $this->authorizeOwned('update', $notification);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return back()->with('status', 'Notification marked as read.');
    }
}
