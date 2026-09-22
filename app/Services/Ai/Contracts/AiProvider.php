<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\Dto\AiChatResult;

interface AiProvider
{
    public function name(): string;

    public function model(): string;

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, bool $json = true): AiChatResult;
}
