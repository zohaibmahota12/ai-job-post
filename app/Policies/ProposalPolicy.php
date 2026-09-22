<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Proposal $proposal): bool
    {
        return $user->is($proposal->user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Proposal $proposal): bool
    {
        return $user->is($proposal->user);
    }

    public function delete(User $user, Proposal $proposal): bool
    {
        return $user->is($proposal->user);
    }
}
