<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotification;

class UserNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserNotification $userNotification): bool
    {
        return $user->is($userNotification->user);
    }

    public function update(User $user, UserNotification $userNotification): bool
    {
        return $user->is($userNotification->user);
    }
}
