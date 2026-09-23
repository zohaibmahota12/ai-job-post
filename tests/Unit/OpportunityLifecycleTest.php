<?php

namespace Tests\Unit;

use App\Models\Opportunity;
use App\Models\Source;
use App\OpportunityStatus;
use App\Services\Opportunities\OpportunityLifecycle;
use App\Services\Opportunities\OpportunityNormalizer;
use App\Sources\RawOpportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_expire_past_deadlines_marks_open_opportunities_expired(): void
    {
        $source = Source::factory()->create();

        $expired = Opportunity::factory()->create([
            'source_id' => $source->id,
            'status' => OpportunityStatus::Open,
            'deadline_at' => now()->subDay(),
            'content_hash' => hash('sha256', 'expired-one'),
        ]);

        $active = Opportunity::factory()->create([
            'source_id' => $source->id,
            'status' => OpportunityStatus::Open,
            'deadline_at' => now()->addDay(),
            'content_hash' => hash('sha256', 'active-one'),
        ]);

        $noDeadline = Opportunity::factory()->create([
            'source_id' => $source->id,
            'status' => OpportunityStatus::Open,
            'deadline_at' => null,
            'content_hash' => hash('sha256', 'no-deadline'),
        ]);

        $count = app(OpportunityLifecycle::class)->expirePastDeadlines();

        $this->assertSame(1, $count);
        $this->assertSame(OpportunityStatus::Expired, $expired->fresh()->status);
        $this->assertSame(OpportunityStatus::Open, $active->fresh()->status);
        $this->assertSame(OpportunityStatus::Open, $noDeadline->fresh()->status);
    }

    public function test_normalizer_marks_explicit_closed_listing(): void
    {
        $source = Source::factory()->create();
        $normalizer = app(OpportunityNormalizer::class);

        $normalized = $normalizer->normalize(new RawOpportunity(
            title: 'Closed Role',
            sourceUrl: 'https://example.com/jobs/closed',
            listingStatus: 'closed',
        ), $source);

        $this->assertSame(OpportunityStatus::Closed, $normalized->status);
    }

    public function test_normalizer_marks_past_deadline_as_expired(): void
    {
        $source = Source::factory()->create();
        $normalizer = app(OpportunityNormalizer::class);

        $normalized = $normalizer->normalize(new RawOpportunity(
            title: 'Past Deadline',
            sourceUrl: 'https://example.com/jobs/past',
            deadlineAt: now()->subHour()->toDateTimeString(),
        ), $source);

        $this->assertSame(OpportunityStatus::Expired, $normalized->status);
    }
}
