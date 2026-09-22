<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\UserNotification;

class NotificationService
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public function notify(User $user, string $type, string $title, ?string $body = null, ?array $data = null): UserNotification
    {
        return $user->userNotifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'read_at' => null,
        ]);
    }

    public function markRead(UserNotification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }
    }

    public function markAllRead(User $user): int
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function unreadCount(User $user): int
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}
