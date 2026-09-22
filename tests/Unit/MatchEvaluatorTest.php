<?php

namespace Tests\Unit;

use App\CriterionOutcome;
use App\JobType;
use App\Models\Opportunity;
use App\Models\User;
use App\RemotePreference;
use App\Services\Matching\MatchEvaluator;
use App\Services\Skills\SkillAttacher;
use App\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_matching_compares_each_users_profile_without_assigning_a_score(): void
    {
        $attacher = app(SkillAttacher::class);
        $evaluator = app(MatchEvaluator::class);

        $laravelUser = User::factory()->create();
        $laravelUser->profile->update([
            'preferred_job_type' => JobType::Freelance,
            'remote_preference' => RemotePreference::Remote,
            'location' => 'Lisbon',
            'minimum_budget' => 1000,
            'preferred_currency' => 'USD',
            'keywords' => ['dashboard'],
            'excluded_keywords' => ['unpaid'],
            'years_of_experience' => 6,
        ]);
        $attacher->attachToUser($laravelUser, 'Laravel');

        $pythonUser = User::factory()->create();
        $pythonUser->profile->update([
            'preferred_job_type' => JobType::FullTime,
            'remote_preference' => RemotePreference::Onsite,
            'keywords' => ['django'],
            'excluded_keywords' => [],
            'years_of_experience' => 1,
        ]);
        $attacher->attachToUser($pythonUser, 'Python');

        $opportunity = Opportunity::factory()->create([
            'title' => 'Laravel dashboard for a remote team',
            'description' => 'Build a paid dashboard.',
            'job_type' => JobType::Freelance,
            'workplace' => Workplace::Remote,
            'location' => 'Lisbon',
            'budget_min' => 2000,
            'budget_max' => 4000,
            'currency' => 'USD',
            'required_experience_years' => 4,
        ]);
        $attacher->attachNamesToOpportunity($opportunity, ['Laravel']);

        $laravelMatch = $evaluator->evaluate($laravelUser->fresh(), $opportunity->fresh());
        $pythonMatch = $evaluator->evaluate($pythonUser->fresh(), $opportunity->fresh());

        $this->assertNull($laravelMatch->score());
        $this->assertSame(CriterionOutcome::Pass, $laravelMatch->outcomeFor('skills'));
        $this->assertSame(CriterionOutcome::Pass, $laravelMatch->outcomeFor('keywords'));
        $this->assertSame(CriterionOutcome::Pass, $laravelMatch->outcomeFor('job_type'));
        $this->assertSame(CriterionOutcome::Pass, $laravelMatch->outcomeFor('budget'));
        $this->assertSame(CriterionOutcome::Pass, $laravelMatch->outcomeFor('experience'));
        $this->assertSame(CriterionOutcome::Fail, $pythonMatch->outcomeFor('skills'));
        $this->assertSame(CriterionOutcome::Fail, $pythonMatch->outcomeFor('job_type'));
        $this->assertSame(CriterionOutcome::Fail, $pythonMatch->outcomeFor('experience'));
    }
}
