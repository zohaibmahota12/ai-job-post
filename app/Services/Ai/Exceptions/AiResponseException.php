<?php

namespace App\Services\Ai\Exceptions;

class AiResponseException extends AiException
{
    public function userMessage(): string
    {
        return 'The AI response could not be used. Your existing proposal was left unchanged.';
    }
}
