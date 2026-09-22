<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Application $application): bool
    {
        return $user->is($application->user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Application $application): bool
    {
        return $user->is($application->user);
    }

    public function delete(User $user, Application $application): bool
    {
        return $user->is($application->user);
    }
}
