<?php

namespace Tests\Unit;

use App\Models\Source;
use App\Services\Opportunities\OpportunityDeduplicator;
use App\Services\Opportunities\OpportunityNormalizer;
use App\Sources\RawOpportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalizer_and_deduplicator_treat_tracking_urls_as_the_same_listing(): void
    {
        $source = Source::factory()->create();
        $normalizer = app(OpportunityNormalizer::class);
        $deduplicator = app(OpportunityDeduplicator::class);

        $first = $normalizer->normalize(new RawOpportunity(
            title: '  Node.js API  ',
            company: 'Northwind',
            sourceUrl: 'https://www.example.com/jobs/9?utm_source=newsletter',
            budgetMin: '5000',
            budgetMax: '1000',
            currency: 'eur',
            jobType: 'contract',
        ), $source);

        $second = $normalizer->normalize(new RawOpportunity(
            title: 'Node.js API',
            company: 'Northwind',
            sourceUrl: 'https://example.com/jobs/9',
            externalId: 'other-source',
        ), $source);

        $this->assertSame('Node.js API', $first->title);
        $this->assertSame('1000.00', $first->budgetMin);
        $this->assertSame('5000.00', $first->budgetMax);
        $this->assertSame('EUR', $first->currency);
        $this->assertSame('contract', $first->jobType?->value);
        $this->assertSame($deduplicator->fingerprint($first), $deduplicator->fingerprint($second));
    }
}
