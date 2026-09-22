<?php

namespace App\Observers;

use App\JobType;
use App\Models\User;
use App\RemotePreference;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->profile()->create([
            'preferred_job_type' => JobType::Any,
            'remote_preference' => RemotePreference::Any,
            'preferred_currency' => 'USD',
            'keywords' => [],
            'excluded_keywords' => [],
        ]);
    }
}
