<?php

namespace App\Policies;

use App\Models\SavedOpportunity;
use App\Models\User;

class SavedOpportunityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SavedOpportunity $savedOpportunity): bool
    {
        return $user->is($savedOpportunity->user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, SavedOpportunity $savedOpportunity): bool
    {
        return $user->is($savedOpportunity->user);
    }
}
