<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    protected function authorizeOwned(string $ability, mixed $model): void
    {
        abort_unless(request()->user()?->can($ability, $model) ?? false, 404);
    }
}
