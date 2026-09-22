<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Services\Matching\Criteria\BudgetCriterion;
use App\Services\Matching\Criteria\ExperienceCriterion;
use App\Services\Matching\Criteria\JobTypeCriterion;
use App\Services\Matching\Criteria\KeywordCriterion;
use App\Services\Matching\Criteria\LocationCriterion;
use App\Services\Matching\Criteria\SkillOverlapCriterion;
use App\Services\Matching\Criteria\WorkplaceCriterion;
use App\Services\Matching\MatchEvaluator;
use App\Sources\AgentReachSourceAdapter;
use App\Sources\SourceManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SourceManager::class, function ($app): SourceManager {
            $manager = new SourceManager;
            $manager->register($app->make(AgentReachSourceAdapter::class));

            return $manager;
        });

        $this->app->singleton(MatchEvaluator::class, function (): MatchEvaluator {
            return new MatchEvaluator([
                new SkillOverlapCriterion,
                new KeywordCriterion,
                new JobTypeCriterion,
                new WorkplaceCriterion,
                new LocationCriterion,
                new BudgetCriterion,
                new ExperienceCriterion,
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Paginator::useTailwind();

        Password::defaults(function () {
            return Password::min(12)->letters()->mixedCase()->numbers();
        });

        RateLimiter::for('login', function (Request $request) {
            $email = mb_strtolower($request->string('email')->toString());

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-email', function (Request $request) {
            $email = mb_strtolower($request->string('email')->toString());

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });
    }
}
