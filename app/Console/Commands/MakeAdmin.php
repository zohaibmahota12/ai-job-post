<?php

namespace App\Console\Commands;

use App\Models\User;
use App\UserRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:make-admin {email : Email of an existing user}')]
#[Description('Grant the admin role to an existing user')]
class MakeAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        if (! is_string($email)) {
            $this->error('Email is required.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error('No user exists with that email.');

            return self::FAILURE;
        }

        $user->forceFill(['role' => UserRole::Admin])->save();
        $this->info('Admin role granted to '.$user->email.'.');

        return self::SUCCESS;
    }
}
