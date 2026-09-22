<?php

namespace Tests\Unit;

use App\Sources\Http\SafeHttpFetcher;
use App\Sources\SourceCollectionException;
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
}
