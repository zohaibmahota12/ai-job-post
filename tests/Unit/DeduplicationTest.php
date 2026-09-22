<?php

namespace Tests\Unit;

use App\Models\Opportunity;
use App\Models\Source;
use App\Services\Opportunities\OpportunityDeduplicator;
use App\Services\Opportunities\OpportunityNormalizer;
use App\Sources\RawOpportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeduplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_source_external_id_matches(): void
    {
        $source = Source::factory()->create();
        $normalizer = app(OpportunityNormalizer::class);
        $deduplicator = app(OpportunityDeduplicator::class);

        $normalized = $normalizer->normalize(new RawOpportunity(
            title: 'Role A',
            company: 'Acme',
            sourceUrl: 'https://example.com/a',
            externalId: 'ext-1',
        ), $source);

        Opportunity::factory()->create([
            'source_id' => $source->id,
            'external_id' => 'ext-1',
            'title' => 'Older title',
            'content_hash' => hash('sha256', 'different'),
        ]);

        $existing = $deduplicator->findExisting($normalized);

        $this->assertNotNull($existing);
        $this->assertSame('ext-1', $existing->external_id);
    }

    public function test_same_canonical_url_matches_despite_tracking_params(): void
    {
        $source = Source::factory()->create();
        $normalizer = app(OpportunityNormalizer::class);
        $deduplicator = app(OpportunityDeduplicator::class);

        $first = $normalizer->normalize(new RawOpportunity(
            title: 'Role B',
            company: 'Acme',
            sourceUrl: 'https://www.example.com/jobs/9?utm_source=newsletter',
        ), $source);

        Opportunity::query()->create([
            'source_id' => $source->id,
            'title' => 'Role B',
            'company' => 'Acme',
            'source_url' => 'https://example.com/jobs/9',
            'canonical_url' => $deduplicator->canonicalUrl('https://example.com/jobs/9'),
            'content_hash' => $deduplicator->fingerprint($first),
            'status' => 'open',
        ]);

        $second = $normalizer->normalize(new RawOpportunity(
            title: 'Role B changed title should still match URL',
            company: 'Other',
            sourceUrl: 'https://example.com/jobs/9?ref=1',
        ), $source);

        $existing = $deduplicator->findExisting($second);

        $this->assertNotNull($existing);
        $this->assertSame('example.com/jobs/9', $existing->canonical_url);
    }

    public function test_fallback_fingerprint_uses_title_company_and_canonical_url(): void
    {
        $source = Source::factory()->create();
        $normalizer = app(OpportunityNormalizer::class);
        $deduplicator = app(OpportunityDeduplicator::class);

        $first = $normalizer->normalize(new RawOpportunity(
            title: 'Fingerprint Role',
            company: 'Northwind',
            sourceUrl: 'https://www.example.com/jobs/f?utm=1',
        ), $source);

        Opportunity::query()->create([
            'source_id' => $source->id,
            'title' => $first->title,
            'company' => $first->company,
            'source_url' => $first->sourceUrl,
            'canonical_url' => $first->canonicalUrl,
            'content_hash' => $deduplicator->fingerprint($first),
            'status' => 'open',
        ]);

        $second = $normalizer->normalize(new RawOpportunity(
            title: 'Fingerprint Role',
            company: 'Northwind',
            sourceUrl: 'https://example.com/jobs/f',
        ), $source);

        $this->assertSame($deduplicator->fingerprint($first), $deduplicator->fingerprint($second));
        $this->assertNotNull($deduplicator->findExisting($second));
    }
}
