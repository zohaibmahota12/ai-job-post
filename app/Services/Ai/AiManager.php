<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiProvider;
use App\Services\Ai\Exceptions\AiDisabledException;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Providers\OpenAiProvider;
use Illuminate\Contracts\Container\Container;

class AiManager
{
    public function __construct(private Container $container) {}

    public function enabled(): bool
    {
        return (bool) config('ai.enabled');
    }

    public function provider(?string $name = null): AiProvider
    {
        if (! $this->enabled()) {
            throw new AiDisabledException('AI proposal generation is disabled.');
        }

        $name = $name ?? (string) config('ai.provider', 'openai');

        return match ($name) {
            'openai' => $this->container->make(OpenAiProvider::class),
            default => throw new AiProviderException("Unsupported AI provider [{$name}]."),
        };
    }
}
