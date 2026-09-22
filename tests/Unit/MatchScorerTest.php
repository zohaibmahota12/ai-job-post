<?php

namespace Tests\Unit;

use App\CriterionOutcome;
use App\JobType;
use App\Models\Opportunity;
use App\Models\User;
use App\RemotePreference;
use App\Services\Matching\MatchEvaluator;
use App\Services\Matching\MatchScorer;
use App\Services\Skills\SkillAttacher;
use App\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchScorerTest extends TestCase
{
    use RefreshDatabase;

    public function test_score_breakdown_uses_configured_weights(): void
    {
        $attacher = app(SkillAttacher::class);
        $evaluator = app(MatchEvaluator::class);
        $scorer = app(MatchScorer::class);

        $user = User::factory()->create();
        $user->profile->update([
            'preferred_job_type' => JobType::Freelance,
            'remote_preference' => RemotePreference::Remote,
            'location' => 'Lisbon',
            'minimum_budget' => 1000,
            'preferred_currency' => 'USD',
            'keywords' => ['dashboard', 'api'],
            'excluded_keywords' => [],
            'years_of_experience' => 6,
        ]);
        $attacher->attachToUser($user, 'Laravel');
        $attacher->attachToUser($user, 'PHP');

        $opportunity = Opportunity::factory()->create([
            'title' => 'Laravel dashboard and API',
            'description' => 'Build a paid dashboard.',
            'job_type' => JobType::Freelance,
            'workplace' => Workplace::Remote,
            'location' => 'Lisbon',
            'budget_min' => 2000,
            'budget_max' => 4000,
            'currency' => 'USD',
            'required_experience_years' => 4,
        ]);
        $attacher->attachNamesToOpportunity($opportunity, ['Laravel', 'Vue']);

        $evaluation = $evaluator->evaluate($user->fresh(), $opportunity->fresh());
        $scored = $scorer->score($user->fresh(), $opportunity->fresh(), $evaluation);

        $this->assertSame(30, $scored->factor('skills')?->max);
        $this->assertSame(15, $scored->factor('skills')?->score);
        $this->assertSame(CriterionOutcome::Pass, $scored->factor('skills')?->status);
        $this->assertSame(20, $scored->factor('keywords')?->score);
        $this->assertSame(15, $scored->factor('experience')?->score);
        $this->assertSame(10, $scored->factor('job_type')?->score);
        $this->assertSame(10, $scored->factor('workplace')?->score);
        $this->assertSame(0, $scored->factor('location')?->score);
        $this->assertSame(CriterionOutcome::Unknown, $scored->factor('location')?->status);
        $this->assertSame(10, $scored->factor('budget')?->score);
        $this->assertSame(80, $scored->score);
        $this->assertArrayHasKey('factors', $scored->toArray());
    }

    public function test_unknown_factors_earn_zero_without_fake_precision(): void
    {
        $evaluator = app(MatchEvaluator::class);
        $scorer = app(MatchScorer::class);

        $user = User::factory()->create();
        $user->profile->update([
            'preferred_job_type' => JobType::Any,
            'remote_preference' => RemotePreference::Any,
            'keywords' => [],
            'excluded_keywords' => [],
            'years_of_experience' => null,
            'minimum_budget' => null,
            'location' => null,
        ]);

        $opportunity = Opportunity::factory()->create([
            'job_type' => null,
            'workplace' => Workplace::Unspecified,
            'location' => null,
            'budget_min' => null,
            'budget_max' => null,
            'required_experience_years' => null,
        ]);

        $scored = $scorer->score(
            $user->fresh(),
            $opportunity->fresh(),
            $evaluator->evaluate($user->fresh(), $opportunity->fresh()),
        );

        $this->assertSame(0, $scored->score);
        $this->assertSame(CriterionOutcome::Unknown, $scored->factor('skills')?->status);
        $this->assertSame(CriterionOutcome::Unknown, $scored->factor('keywords')?->status);
        $this->assertSame(CriterionOutcome::Unknown, $scored->factor('experience')?->status);
    }

    public function test_partial_keyword_match_is_proportional(): void
    {
        $evaluator = app(MatchEvaluator::class);
        $scorer = app(MatchScorer::class);

        $user = User::factory()->create();
        $user->profile->update([
            'keywords' => ['laravel', 'vue', 'golang'],
            'excluded_keywords' => [],
        ]);

        $opportunity = Opportunity::factory()->create([
            'title' => 'Laravel engineer',
            'description' => 'Need Laravel experience.',
        ]);

        $scored = $scorer->score(
            $user->fresh(),
            $opportunity->fresh(),
            $evaluator->evaluate($user->fresh(), $opportunity->fresh()),
        );

        $this->assertSame(7, $scored->factor('keywords')?->score);
        $this->assertSame(CriterionOutcome::Pass, $scored->factor('keywords')?->status);
    }
}
