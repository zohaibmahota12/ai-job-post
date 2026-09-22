<?php

namespace Tests\Feature;

use App\JobType;
use App\Models\Opportunity;
use App\Models\OpportunityMatch;
use App\Models\SavedOpportunity;
use App\Models\User;
use App\Services\Matching\MatchSynchronizer;
use App\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_match_rows_are_unique_per_user_and_opportunity(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create();
        $synchronizer = app(MatchSynchronizer::class);

        $first = $synchronizer->syncPair($user, $opportunity);
        $second = $synchronizer->syncPair($user, $opportunity);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, OpportunityMatch::query()->count());
        $this->assertNotNull($second->score);
        $this->assertIsArray($second->reasons);
        $this->assertArrayHasKey('factors', $second->reasons);
    }

    public function test_users_cannot_see_another_users_match_score_on_listing_pages(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $opportunity = Opportunity::factory()->create(['title' => 'Shared listing']);

        OpportunityMatch::factory()->create([
            'user_id' => $owner->id,
            'opportunity_id' => $opportunity->id,
            'score' => 99,
            'reasons' => ['score' => 99, 'factors' => []],
        ]);

        $this->actingAs($other)
            ->get(route('opportunities.index'))
            ->assertOk()
            ->assertSee('Shared listing')
            ->assertDontSee('99');
    }

    public function test_profile_update_recalculates_matches(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create([
            'title' => 'Need dashboard help',
            'job_type' => JobType::Freelance,
            'workplace' => Workplace::Remote,
        ]);

        app(MatchSynchronizer::class)->syncPair($user, $opportunity);
        $before = OpportunityMatch::query()->where('user_id', $user->id)->value('score');

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'bio' => 'Updated',
            'experience' => null,
            'years_of_experience' => 5,
            'location' => 'Remote',
            'preferred_job_type' => JobType::Freelance->value,
            'remote_preference' => 'remote',
            'minimum_budget' => null,
            'preferred_currency' => 'USD',
            'keywords' => 'dashboard',
            'excluded_keywords' => '',
        ])->assertRedirect();

        $after = OpportunityMatch::query()->where('user_id', $user->id)->value('score');

        $this->assertNotNull($after);
        $this->assertNotEquals($before, $after);
    }

    public function test_opportunity_detail_persists_match_for_viewer_only(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $opportunity = Opportunity::factory()->create();

        $this->actingAs($viewer)
            ->get(route('opportunities.show', $opportunity))
            ->assertOk()
            ->assertSee('Match score');

        $this->assertDatabaseHas('opportunity_matches', [
            'user_id' => $viewer->id,
            'opportunity_id' => $opportunity->id,
        ]);
        $this->assertDatabaseMissing('opportunity_matches', [
            'user_id' => $other->id,
            'opportunity_id' => $opportunity->id,
        ]);
    }

    public function test_saved_opportunities_remain_user_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $opportunity = Opportunity::factory()->create();
        $saved = SavedOpportunity::factory()->create([
            'user_id' => $owner->id,
            'opportunity_id' => $opportunity->id,
        ]);

        $this->actingAs($other)->delete(route('saved.destroy', $saved))->assertNotFound();
        $this->assertDatabaseHas('saved_opportunities', ['id' => $saved->id]);
    }
}
