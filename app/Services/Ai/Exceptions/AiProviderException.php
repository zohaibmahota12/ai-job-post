<?php

namespace App\Services\Ai\Exceptions;

class AiProviderException extends AiException
{
    public function userMessage(): string
    {
        return 'The AI provider could not complete this request. Your existing proposal was left unchanged.';
    }
}
