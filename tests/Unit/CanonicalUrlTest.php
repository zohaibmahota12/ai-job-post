<?php

namespace Tests\Unit;

use App\Services\Opportunities\OpportunityDeduplicator;
use Tests\TestCase;

class CanonicalUrlTest extends TestCase
{
    public function test_strips_tracking_parameters_and_trailing_slash(): void
    {
        $deduplicator = app(OpportunityDeduplicator::class);

        $this->assertSame(
            'example.com/jobs/9',
            $deduplicator->canonicalUrl('https://www.example.com/jobs/9/?utm_source=newsletter&utm_medium=email'),
        );
    }

    public function test_keeps_meaningful_query_parameters_sorted(): void
    {
        $deduplicator = app(OpportunityDeduplicator::class);

        $this->assertSame(
            'example.com/jobs/view?id=42&lang=en',
            $deduplicator->canonicalUrl('https://example.com/jobs/view?lang=en&id=42&utm_campaign=spring'),
        );
    }

    public function test_different_meaningful_query_values_remain_distinct(): void
    {
        $deduplicator = app(OpportunityDeduplicator::class);

        $this->assertNotSame(
            $deduplicator->canonicalUrl('https://example.com/jobs/view?id=1'),
            $deduplicator->canonicalUrl('https://example.com/jobs/view?id=2'),
        );
    }
}
