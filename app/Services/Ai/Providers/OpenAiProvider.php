<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiProvider;
use App\Services\Ai\Dto\AiChatResult;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'openai';
    }

    public function model(): string
    {
        return (string) config('ai.providers.openai.model', 'gpt-4o-mini');
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, bool $json = true): AiChatResult
    {
        $apiKey = (string) config('ai.providers.openai.api_key', '');
        $baseUrl = rtrim((string) config('ai.providers.openai.base_url', 'https://api.openai.com/v1'), '/');

        if ($apiKey === '') {
            throw new AiProviderException('AI API key is not configured.');
        }

        $payload = [
            'model' => $this->model(),
            'messages' => $messages,
            'temperature' => 0.4,
            'max_tokens' => (int) config('ai.max_tokens', 1200),
        ];

        if ($json) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout((float) config('ai.connect_timeout', 5))
                ->timeout((float) config('ai.timeout', 30))
                ->withHeaders([
                    'User-Agent' => 'OpportunityHunter/3.0',
                ])
                ->post('/chat/completions', $payload)
                ->throw();
        } catch (ConnectionException $exception) {
            Log::warning('AI provider connection failed.', [
                'provider' => $this->name(),
                'model' => $this->model(),
                'error' => $exception->getMessage(),
            ]);

            throw new AiProviderException('AI provider connection failed.', previous: $exception);
        } catch (RequestException $exception) {
            $status = $exception->response?->status();

            Log::warning('AI provider HTTP error.', [
                'provider' => $this->name(),
                'model' => $this->model(),
                'status' => $status,
            ]);

            $message = match (true) {
                $status === 401, $status === 403 => 'AI provider credentials were rejected.',
                $status === 429 => 'AI provider rate limit reached.',
                default => 'AI provider request failed.',
            };

            throw new AiProviderException($message, previous: $exception);
        } catch (Throwable $exception) {
            Log::warning('AI provider unexpected failure.', [
                'provider' => $this->name(),
                'model' => $this->model(),
                'error' => $exception->getMessage(),
            ]);

            throw new AiProviderException('AI provider request failed.', previous: $exception);
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new AiProviderException('AI provider returned an empty response.');
        }

        return new AiChatResult(
            content: $content,
            provider: $this->name(),
            model: $this->model(),
            promptTokens: is_numeric(data_get($response->json(), 'usage.prompt_tokens'))
                ? (int) data_get($response->json(), 'usage.prompt_tokens')
                : null,
            completionTokens: is_numeric(data_get($response->json(), 'usage.completion_tokens'))
                ? (int) data_get($response->json(), 'usage.completion_tokens')
                : null,
        );
    }
}
