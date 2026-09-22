<?php

namespace App\Services\Ai\Exceptions;

use RuntimeException;

class AiException extends RuntimeException
{
    public function userMessage(): string
    {
        return 'AI proposal generation failed. You can still create and edit a proposal manually.';
    }
}
