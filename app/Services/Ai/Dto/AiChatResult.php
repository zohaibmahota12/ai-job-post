<?php

namespace App\Services\Ai\Dto;

readonly class AiChatResult
{
    public function __construct(
        public string $content,
        public string $provider,
        public string $model,
        public ?int $promptTokens = null,
        public ?int $completionTokens = null,
    ) {}
}
