<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Services\Notifications\NotificationService;
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
            'unreadCount' => app(NotificationService::class)->unreadCount($user),
        ]);
    }

    public function update(UserNotification $notification, NotificationService $notifications): RedirectResponse
    {
        $this->authorizeOwned('update', $notification);
        $notifications->markRead($notification);

        return back()->with('status', 'Notification marked as read.');
    }

    public function markAllRead(Request $request, NotificationService $notifications): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $notifications->markAllRead($user);

        return back()->with('status', 'All notifications marked as read.');
    }
}
