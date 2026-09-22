<?php

namespace Tests\Unit;

use App\Sources\Http\DnsLookup;
use App\Sources\Http\SafeHttpFetcher;
use App\Sources\SourceCollectionException;
use Illuminate\Support\Facades\Http;
use Tests\Support\PublicAddressDnsLookup;
use Tests\TestCase;

class SafeHttpFetcherTest extends TestCase
{
    public function test_rejects_invalid_url(): void
    {
        $this->expectException(SourceCollectionException::class);
        app(SafeHttpFetcher::class)->assertSafeUrl('not-a-url');
    }

    public function test_rejects_non_http_schemes(): void
    {
        $this->expectException(SourceCollectionException::class);
        app(SafeHttpFetcher::class)->assertSafeUrl('file:///etc/passwd');
    }

    public function test_rejects_localhost(): void
    {
        $this->expectException(SourceCollectionException::class);
        app(SafeHttpFetcher::class)->assertSafeUrl('http://localhost/feed.xml');
    }

    public function test_rejects_private_ipv4(): void
    {
        $fetcher = app(SafeHttpFetcher::class);

        foreach (['http://127.0.0.1/x', 'http://10.0.0.5/x', 'http://192.168.1.10/x', 'http://169.254.1.1/x'] as $url) {
            try {
                $fetcher->assertSafeUrl($url);
                $this->fail('Expected SSRF rejection for '.$url);
            } catch (SourceCollectionException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_rejects_urls_with_embedded_credentials(): void
    {
        $this->expectException(SourceCollectionException::class);
        app(SafeHttpFetcher::class)->assertSafeUrl('https://user:secret@example.com/feed.xml');
    }

    public function test_accepts_public_https_url_without_dns_when_host_is_literal_public_ip(): void
    {
        app(SafeHttpFetcher::class)->assertSafeUrl('https://93.184.216.34/feed.xml');
        $this->assertTrue(true);
    }

    public function test_rejects_hostname_that_resolves_to_private_address(): void
    {
        $this->app->instance(DnsLookup::class, new class implements DnsLookup
        {
            public function resolve(string $host): array
            {
                return ['127.0.0.1'];
            }
        });

        $this->expectException(SourceCollectionException::class);
        $this->expectExceptionMessage('private or internal network');
        app(SafeHttpFetcher::class)->assertSafeUrl('https://evil.example/feed.xml');
    }

    public function test_rejects_unresolvable_hostname(): void
    {
        $this->app->instance(DnsLookup::class, new class implements DnsLookup
        {
            public function resolve(string $host): array
            {
                return [];
            }
        });

        $this->expectException(SourceCollectionException::class);
        $this->expectExceptionMessage('could not be resolved');
        app(SafeHttpFetcher::class)->assertSafeUrl('https://missing.example/feed.xml');
    }

    public function test_http_fake_is_reached_when_dns_lookup_is_injected(): void
    {
        $this->app->instance(DnsLookup::class, new PublicAddressDnsLookup);

        Http::preventStrayRequests();
        Http::fake([
            'https://example.com/feed.xml' => Http::response('ok', 200),
        ]);

        $result = app(SafeHttpFetcher::class)->get('https://example.com/feed.xml');

        $this->assertSame('ok', $result['body']);
        $this->assertSame(200, $result['status']);
        Http::assertSentCount(1);
    }
}
