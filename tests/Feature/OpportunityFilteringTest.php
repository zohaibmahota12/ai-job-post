<?php

namespace Tests\Feature;

use App\JobType;
use App\Models\Opportunity;
use App\Models\OpportunityMatch;
use App\Models\SavedOpportunity;
use App\Models\Source;
use App\Models\User;
use App\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_opportunities_by_score_job_type_workplace_source_and_saved(): void
    {
        $user = User::factory()->create();
        $source = Source::factory()->create(['name' => 'Filter Source']);

        $high = Opportunity::factory()->create([
            'source_id' => $source->id,
            'title' => 'High Match Role',
            'job_type' => JobType::Freelance,
            'workplace' => Workplace::Remote,
            'location' => 'Berlin',
        ]);
        $low = Opportunity::factory()->create([
            'title' => 'Low Match Role',
            'job_type' => JobType::FullTime,
            'workplace' => Workplace::Onsite,
            'location' => 'Paris',
        ]);

        OpportunityMatch::factory()->create([
            'user_id' => $user->id,
            'opportunity_id' => $high->id,
            'score' => 80,
        ]);
        OpportunityMatch::factory()->create([
            'user_id' => $user->id,
            'opportunity_id' => $low->id,
            'score' => 20,
        ]);
        SavedOpportunity::factory()->create([
            'user_id' => $user->id,
            'opportunity_id' => $high->id,
        ]);

        $this->actingAs($user)
            ->get(route('opportunities.index', [
                'min_score' => 50,
                'job_type' => JobType::Freelance->value,
                'workplace' => Workplace::Remote->value,
                'location' => 'Berlin',
                'source_id' => $source->id,
                'saved' => '1',
            ]))
            ->assertOk()
            ->assertSee('High Match Role')
            ->assertDontSee('Low Match Role');
    }
}
