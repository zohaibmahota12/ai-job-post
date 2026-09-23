<?php

namespace Tests\Unit;

use App\Sources\Http\DnsLookup;
use App\Sources\Http\SafeHttpFetcher;
use App\Sources\SourceCollectionException;
use Illuminate\Support\Facades\Http;
use Tests\Support\PublicAddressDnsLookup;
use Tests\TestCase;

class HttpRetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(DnsLookup::class, new PublicAddressDnsLookup);
        config([
            'opportunity.http.max_retries' => 2,
            'opportunity.http.retry_delay_ms' => 0,
        ]);
    }

    public function test_retries_transient_connection_failure_then_succeeds(): void
    {
        Http::fake([
            'https://example.com/feed.xml' => Http::sequence()
                ->pushFailedConnection('Connection timed out')
                ->pushFailedConnection('Connection timed out')
                ->push('<rss></rss>', 200),
        ]);

        $result = app(SafeHttpFetcher::class)->get('https://example.com/feed.xml');

        $this->assertSame(200, $result['status']);
        Http::assertSentCount(3);
    }

    public function test_retries_5xx_then_succeeds(): void
    {
        Http::fake([
            'https://example.com/feed.xml' => Http::sequence()
                ->push('unavailable', 503)
                ->push('unavailable', 503)
                ->push('<rss></rss>', 200),
        ]);

        $result = app(SafeHttpFetcher::class)->get('https://example.com/feed.xml');

        $this->assertSame(200, $result['status']);
        Http::assertSentCount(3);
    }

    public function test_does_not_retry_4xx_responses(): void
    {
        Http::fake([
            'https://example.com/missing.xml' => Http::response('gone', 404),
        ]);

        try {
            app(SafeHttpFetcher::class)->get('https://example.com/missing.xml');
            $this->fail('Expected SourceCollectionException');
        } catch (SourceCollectionException $exception) {
            $this->assertStringContainsString('HTTP 404', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }
}
