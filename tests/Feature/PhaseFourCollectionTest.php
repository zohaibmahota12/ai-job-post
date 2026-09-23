<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\OpportunityMatch;
use App\Models\Source;
use App\Models\SystemError;
use App\Models\User;
use App\OpportunityStatus;
use App\SourceHealth;
use App\Sources\CollectionResult;
use App\Sources\Http\DnsLookup;
use App\Sources\RawOpportunity;
use App\Sources\SourceAdapter;
use App\Sources\SourceManager;
use Database\Seeders\SourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\PublicAddressDnsLookup;
use Tests\TestCase;

class PhaseFourCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(DnsLookup::class, new PublicAddressDnsLookup);
        config([
            'opportunity.http.retry_delay_ms' => 0,
            'opportunity.http.max_retries' => 2,
        ]);
    }

    public function test_seeded_real_sources_exist_disabled_with_public_endpoints(): void
    {
        $this->seed(SourceSeeder::class);

        foreach ([
            'remotive_remote_jobs' => 'https://remotive.com/api/remote-jobs',
            'remoteok_api' => 'https://remoteok.com/api',
            'weworkremotely_programming_rss' => 'https://weworkremotely.com/categories/remote-programming-jobs.rss',
            'jobicy_remote_jobs' => 'https://jobicy.com/api/v2/remote-jobs',
        ] as $key => $url) {
            $source = Source::query()->where('key', $key)->first();

            $this->assertNotNull($source, $key);
            $this->assertFalse($source->is_enabled);
            $this->assertSame($url, $source->config['url'] ?? null);
            $this->assertSame(SourceHealth::Disabled, $source->health());
        }

        $this->assertFalse(Source::query()->where('key', 'agent_reach')->value('is_enabled'));
    }

    public function test_remotive_fixture_collection_is_idempotent_and_updates_health(): void
    {
        $source = Source::factory()->jsonApi('https://remotive.com/api/remote-jobs')->create([
            'key' => 'remotive_remote_jobs',
            'is_enabled' => true,
            'config' => [
                'url' => 'https://remotive.com/api/remote-jobs',
                'items_path' => 'jobs',
                'field_map' => [
                    'title' => 'title',
                    'description' => 'description',
                    'company' => 'company_name',
                    'source_url' => 'url',
                    'external_id' => 'id',
                    'location' => 'candidate_required_location',
                    'job_type' => 'job_type',
                    'posted_at' => 'publication_date',
                    'skills' => 'tags',
                ],
            ],
        ]);

        Http::fake([
            'https://remotive.com/api/remote-jobs' => Http::response(
                file_get_contents(base_path('tests/fixtures/feeds/remotive.json')),
                200,
            ),
        ]);

        $this->artisan('opportunities:collect', ['--source' => 'remotive_remote_jobs'])->assertSuccessful();
        $this->artisan('opportunities:collect', ['--source' => 'remotive_remote_jobs'])->assertSuccessful();

        $this->assertSame(2, Opportunity::query()->count());
        $this->assertSame(0, (int) $source->fresh()->consecutive_failures);
        $this->assertSame(2, (int) $source->fresh()->last_item_count);
        $this->assertSame(SourceHealth::Healthy, $source->fresh()->health());
        $this->assertDatabaseHas('source_runs', [
            'source_id' => $source->id,
            'items_created' => 2,
        ]);
        $this->assertDatabaseHas('source_runs', [
            'source_id' => $source->id,
            'items_duplicated' => 2,
        ]);

        $opportunity = Opportunity::query()->where('external_id', '101')->first();
        $this->assertNotNull($opportunity);
        $this->assertStringNotContainsString('<', (string) $opportunity->description);
        $this->assertStringContainsString('Laravel', (string) $opportunity->description);
    }

    public function test_remoteok_skips_legal_metadata_object(): void
    {
        Source::factory()->jsonApi('https://remoteok.com/api')->create([
            'key' => 'remoteok_api',
            'is_enabled' => true,
            'config' => [
                'url' => 'https://remoteok.com/api',
                'items_path' => '',
                'field_map' => [
                    'title' => 'position',
                    'description' => 'description',
                    'company' => 'company',
                    'source_url' => 'url',
                    'external_id' => 'id',
                    'location' => 'location',
                    'posted_at' => 'date',
                    'skills' => 'tags',
                ],
            ],
        ]);

        Http::fake([
            'https://remoteok.com/api' => Http::response(
                file_get_contents(base_path('tests/fixtures/feeds/remoteok.json')),
                200,
            ),
        ]);

        $this->artisan('opportunities:collect', ['--source' => 'remoteok_api'])->assertSuccessful();

        $this->assertSame(2, Opportunity::query()->count());
        $this->assertDatabaseHas('opportunities', ['external_id' => '9001']);
    }

    public function test_weworkremotely_rss_fixture_collects(): void
    {
        Source::factory()->rss('https://weworkremotely.com/categories/remote-programming-jobs.rss')->create([
            'key' => 'weworkremotely_programming_rss',
            'is_enabled' => true,
        ]);

        Http::fake([
            'https://weworkremotely.com/categories/remote-programming-jobs.rss' => Http::response(
                file_get_contents(base_path('tests/fixtures/feeds/weworkremotely.rss')),
                200,
            ),
        ]);

        $this->artisan('opportunities:collect', ['--source' => 'weworkremotely_programming_rss'])->assertSuccessful();

        $this->assertSame(2, Opportunity::query()->count());
        $this->assertDatabaseHas('opportunities', [
            'canonical_url' => 'weworkremotely.com/remote-jobs/112-react-native-engineer',
        ]);
    }

    public function test_jobicy_marks_closed_listing_and_keeps_salary_fields(): void
    {
        Source::factory()->jsonApi('https://jobicy.com/api/v2/remote-jobs')->create([
            'key' => 'jobicy_remote_jobs',
            'is_enabled' => true,
            'config' => [
                'url' => 'https://jobicy.com/api/v2/remote-jobs',
                'items_path' => 'jobs',
                'field_map' => [
                    'title' => 'jobTitle',
                    'description' => 'jobDescription',
                    'company' => 'companyName',
                    'source_url' => 'url',
                    'external_id' => 'id',
                    'location' => 'jobGeo',
                    'job_type' => 'jobType',
                    'posted_at' => 'pubDate',
                    'budget_min' => 'salaryMin',
                    'budget_max' => 'salaryMax',
                    'currency' => 'salaryCurrency',
                    'listing_status' => 'listing_status',
                    'skills' => 'tags',
                ],
            ],
        ]);

        Http::fake([
            'https://jobicy.com/api/v2/remote-jobs' => Http::response(
                file_get_contents(base_path('tests/fixtures/feeds/jobicy.json')),
                200,
            ),
        ]);

        $this->artisan('opportunities:collect', ['--source' => 'jobicy_remote_jobs'])->assertSuccessful();

        $this->assertDatabaseHas('opportunities', [
            'external_id' => '501',
            'currency' => 'USD',
            'status' => OpportunityStatus::Open->value,
        ]);
        $this->assertDatabaseHas('opportunities', [
            'external_id' => '502',
            'status' => OpportunityStatus::Closed->value,
        ]);
    }

    public function test_failed_source_does_not_stop_others_and_tracks_failures(): void
    {
        $failing = Source::factory()->create([
            'key' => 'agent_reach',
            'driver' => 'agent_reach',
            'type' => 'agent_reach',
            'is_enabled' => true,
        ]);

        $ok = Source::factory()->create([
            'key' => 'fixture_ok',
            'driver' => 'fixture_ok',
            'type' => 'fixture',
            'is_enabled' => true,
        ]);

        app(SourceManager::class)->register(new class implements SourceAdapter
        {
            public function driver(): string
            {
                return 'fixture_ok';
            }

            public function type(): string
            {
                return 'fixture';
            }

            public function collect(Source $source): CollectionResult
            {
                return new CollectionResult([
                    new RawOpportunity(
                        title: 'Isolated Success',
                        company: 'Acme',
                        sourceUrl: 'https://example.com/jobs/isolated',
                        externalId: 'iso-1',
                    ),
                ]);
            }
        });

        $this->artisan('opportunities:collect')->assertFailed();

        $this->assertSame(1, Opportunity::query()->count());
        $this->assertSame(1, (int) $failing->fresh()->consecutive_failures);
        $this->assertNotNull($failing->fresh()->last_failure_at);
        $this->assertSame(SourceHealth::Warning, $failing->fresh()->health());
        $this->assertSame(0, (int) $ok->fresh()->consecutive_failures);
        $this->assertSame(SourceHealth::Healthy, $ok->fresh()->health());
    }

    public function test_collection_creates_matches_for_active_verified_users(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->profile->update([
            'keywords' => ['matched'],
        ]);

        Source::factory()->create([
            'key' => 'fixture_match',
            'driver' => 'fixture_match',
            'type' => 'fixture',
            'is_enabled' => true,
        ]);

        app(SourceManager::class)->register(new class implements SourceAdapter
        {
            public function driver(): string
            {
                return 'fixture_match';
            }

            public function type(): string
            {
                return 'fixture';
            }

            public function collect(Source $source): CollectionResult
            {
                return new CollectionResult([
                    new RawOpportunity(
                        title: 'Matched Role',
                        company: 'Match Co',
                        sourceUrl: 'https://example.com/jobs/match-1',
                        externalId: 'match-1',
                        description: 'A matched opportunity description',
                        jobType: 'full_time',
                        workplace: 'remote',
                    ),
                ]);
            }
        });

        $this->artisan('opportunities:collect', ['--source' => 'fixture_match'])->assertSuccessful();

        $this->assertDatabaseHas('opportunity_matches', [
            'user_id' => $user->id,
        ]);
        $this->assertGreaterThan(0, OpportunityMatch::query()->where('user_id', $user->id)->value('score'));
    }

    public function test_malformed_json_and_empty_feed_are_isolated_failures(): void
    {
        $bad = Source::factory()->jsonApi('https://example.com/bad.json')->create([
            'key' => 'bad_json',
            'is_enabled' => true,
        ]);

        Http::fake([
            'https://example.com/bad.json' => Http::response('{not-json', 200),
        ]);

        $this->artisan('opportunities:collect', ['--source' => 'bad_json'])->assertFailed();

        $this->assertSame(1, (int) $bad->fresh()->consecutive_failures);
        $this->assertTrue(SystemError::query()->where('source_run_id', '!=', null)->exists());
        $this->assertDatabaseCount('opportunities', 0);
    }

    public function test_admin_can_enable_disable_and_view_health(): void
    {
        $admin = User::factory()->admin()->create();
        $source = Source::factory()->rss()->create([
            'is_enabled' => false,
            'consecutive_failures' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.sources.index'))
            ->assertOk()
            ->assertSee('Disabled')
            ->assertSee($source->name);

        $this->actingAs($admin)
            ->patch(route('admin.sources.update', $source), ['is_enabled' => 1])
            ->assertRedirect(route('admin.sources.index'));

        $this->assertTrue($source->fresh()->is_enabled);
    }

    public function test_ssrf_protections_remain_intact_for_real_source_style_urls(): void
    {
        Source::factory()->jsonApi('http://127.0.0.1/jobs.json')->create([
            'key' => 'ssrf_source',
            'is_enabled' => true,
        ]);

        $this->artisan('opportunities:collect', ['--source' => 'ssrf_source'])->assertFailed();
        $this->assertDatabaseCount('opportunities', 0);
        $this->assertDatabaseHas('system_errors', []);
    }
}
