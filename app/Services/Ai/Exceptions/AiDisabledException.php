<?php

namespace App\Services\Ai\Exceptions;

class AiDisabledException extends AiException
{
    public function userMessage(): string
    {
        return 'AI proposal generation is currently unavailable. You can still create and edit a proposal manually.';
    }
}
