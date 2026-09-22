<?php

namespace Tests\Unit;

use App\Models\Source;
use App\Sources\Http\DnsLookup;
use App\Sources\RssAtomSourceAdapter;
use App\Sources\SourceCollectionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Support\PublicAddressDnsLookup;
use Tests\TestCase;

class RssAtomSourceAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_parses_valid_rss_fixture(): void
    {
        $source = Source::factory()->rss()->create();
        $adapter = app(RssAtomSourceAdapter::class);
        $xml = file_get_contents(base_path('tests/fixtures/feeds/valid-rss.xml'));

        $items = $adapter->parse($xml, $source);

        $this->assertCount(2, $items);
        $this->assertSame('Senior Laravel Engineer', $items[0]->title);
        $this->assertSame('rss-laravel-1', $items[0]->externalId);
        $this->assertSame('https://jobs.example.com/jobs/laravel-1', $items[0]->sourceUrl);
        $this->assertSame('remote', $items[0]->workplace);
        $this->assertSame('full_time', $items[0]->jobType);
        $this->assertSame('Contract React Native Gig', $items[1]->title);
    }

    public function test_parses_valid_atom_fixture(): void
    {
        $source = Source::factory()->rss()->create(['name' => 'Atom Source']);
        $adapter = app(RssAtomSourceAdapter::class);
        $xml = file_get_contents(base_path('tests/fixtures/feeds/valid-atom.xml'));

        $items = $adapter->parse($xml, $source);

        $this->assertCount(1, $items);
        $this->assertSame('Atom PHP Developer', $items[0]->title);
        $this->assertSame('atom-php-1', $items[0]->externalId);
        $this->assertSame('https://jobs.example.com/atom/php-1', $items[0]->sourceUrl);
        $this->assertSame('Contoso', $items[0]->company);
    }

    public function test_malformed_feed_throws(): void
    {
        $source = Source::factory()->rss()->create();
        $adapter = app(RssAtomSourceAdapter::class);
        $xml = file_get_contents(base_path('tests/fixtures/feeds/malformed.xml'));

        $this->expectException(SourceCollectionException::class);
        $adapter->parse($xml, $source);
    }

    public function test_collect_fetches_configured_url_with_http_fake(): void
    {
        $this->app->instance(DnsLookup::class, new PublicAddressDnsLookup);

        Http::preventStrayRequests();
        Http::fake([
            'https://example.com/feed.xml' => Http::response(
                file_get_contents(base_path('tests/fixtures/feeds/valid-rss.xml')),
                200,
                ['Content-Type' => 'application/rss+xml'],
            ),
        ]);

        $source = Source::factory()->rss('https://example.com/feed.xml')->create(['is_enabled' => true]);
        $result = app(RssAtomSourceAdapter::class)->collect($source);

        $this->assertCount(2, $result->opportunities);
        Http::assertSentCount(1);
    }

    public function test_http_error_is_surfaced(): void
    {
        $this->app->instance(DnsLookup::class, new PublicAddressDnsLookup);

        Http::preventStrayRequests();
        Http::fake([
            'https://example.com/feed.xml' => Http::response('Nope', 503),
        ]);

        $source = Source::factory()->rss('https://example.com/feed.xml')->create();

        $this->expectException(SourceCollectionException::class);
        $this->expectExceptionMessage('HTTP 503');
        app(RssAtomSourceAdapter::class)->collect($source);
    }

    public function test_missing_url_config_fails(): void
    {
        $source = Source::factory()->rss()->create(['config' => []]);

        $this->expectException(SourceCollectionException::class);
        app(RssAtomSourceAdapter::class)->collect($source);
    }

    public function test_timeout_is_surfaced_as_collection_failure(): void
    {
        $this->app->instance(DnsLookup::class, new PublicAddressDnsLookup);

        Http::preventStrayRequests();
        Http::fake([
            'https://example.com/feed.xml' => function () {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        $source = Source::factory()->rss('https://example.com/feed.xml')->create();

        $this->expectException(SourceCollectionException::class);
        $this->expectExceptionMessage('HTTP request failed');
        app(RssAtomSourceAdapter::class)->collect($source);
    }
}
