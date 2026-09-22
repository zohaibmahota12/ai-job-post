<?php

namespace Tests\Unit;

use App\Services\Ai\AiManager;
use App\Services\Ai\Exceptions\AiDisabledException;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Exceptions\AiResponseException;
use App\Services\Ai\GeneratedProposalParser;
use App\Services\Ai\Providers\OpenAiProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderTest extends TestCase
{
    public function test_disabled_ai_manager_throws_disabled_exception(): void
    {
        Config::set('ai.enabled', false);

        $this->expectException(AiDisabledException::class);

        app(AiManager::class)->provider();
    }

    public function test_openai_provider_parses_successful_json_response(): void
    {
        Config::set('ai.enabled', true);
        Config::set('ai.providers.openai.api_key', 'test-key');
        Config::set('ai.providers.openai.model', 'gpt-4o-mini');
        Config::set('ai.providers.openai.base_url', 'https://api.openai.com/v1');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'proposal' => 'I can help with Laravel work.',
                                'subject' => 'Laravel proposal',
                                'key_points' => ['Laravel', 'PHP'],
                            ]),
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 20,
                ],
            ], 200),
        ]);

        $result = app(OpenAiProvider::class)->chat([
            ['role' => 'system', 'content' => 'test'],
            ['role' => 'user', 'content' => 'write'],
        ]);

        $parsed = app(GeneratedProposalParser::class)->parse($result->content);

        $this->assertSame('openai', $result->provider);
        $this->assertSame('gpt-4o-mini', $result->model);
        $this->assertSame('I can help with Laravel work.', $parsed->proposal);
        $this->assertSame('Laravel proposal', $parsed->subject);
        $this->assertSame(['Laravel', 'PHP'], $parsed->keyPoints);

        Http::assertSent(function (Request $request): bool {
            return $request->hasHeader('Authorization', 'Bearer test-key')
                && ! str_contains(json_encode($request->data()), 'test-key');
        });
    }

    public function test_malformed_json_falls_back_to_plain_text_when_possible(): void
    {
        $parsed = app(GeneratedProposalParser::class)->parse('Plain proposal without JSON.');

        $this->assertSame('Plain proposal without JSON.', $parsed->proposal);
        $this->assertNull($parsed->subject);
    }

    public function test_empty_response_is_rejected(): void
    {
        $this->expectException(AiResponseException::class);

        app(GeneratedProposalParser::class)->parse('{"proposal":""}');
    }

    public function test_openai_http_error_becomes_provider_exception(): void
    {
        Config::set('ai.providers.openai.api_key', 'test-key');
        Config::set('ai.providers.openai.base_url', 'https://api.openai.com/v1');

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'nope']], 401),
        ]);

        $this->expectException(AiProviderException::class);

        app(OpenAiProvider::class)->chat([
            ['role' => 'user', 'content' => 'hi'],
        ]);
    }

    public function test_missing_api_key_fails_safely(): void
    {
        Config::set('ai.providers.openai.api_key', '');

        $this->expectException(AiProviderException::class);

        app(OpenAiProvider::class)->chat([
            ['role' => 'user', 'content' => 'hi'],
        ]);
    }

    public function test_parser_strips_html_and_ignores_script_tags(): void
    {
        $parsed = app(GeneratedProposalParser::class)->parse(json_encode([
            'proposal' => '<script>alert(1)</script>Hello <b>world</b>',
            'subject' => '<img src=x onerror=alert(1)>Subject',
        ]));

        $this->assertSame('alert(1)Hello world', $parsed->proposal);
        $this->assertSame('Subject', $parsed->subject);
    }
}
