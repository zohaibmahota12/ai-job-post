<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Source;
use App\Models\User;
use App\Sources\CollectionResult;
use App\Sources\RawOpportunity;
use App\Sources\SourceAdapter;
use App\Sources\SourceManager;
use Database\Seeders\SourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CollectOpportunitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_agent_reach_source_is_disabled(): void
    {
        $this->seed(SourceSeeder::class);

        $source = Source::query()->where('driver', 'agent_reach')->first();

        $this->assertNotNull($source);
        $this->assertFalse($source->is_enabled);
        $this->assertSame('agent_reach', $source->key);
    }

    public function test_disabled_source_is_skipped_without_creating_listings(): void
    {
        Source::factory()->create([
            'key' => 'agent_reach',
            'driver' => 'agent_reach',
            'type' => 'agent_reach',
            'is_enabled' => false,
        ]);

        $this->artisan('opportunities:collect')->assertSuccessful();

        $this->assertDatabaseHas('source_runs', ['status' => 'skipped']);
        $this->assertDatabaseCount('opportunities', 0);
    }

    public function test_enabled_agent_reach_source_fails_without_inventing_listings(): void
    {
        Source::factory()->create([
            'key' => 'agent_reach',
            'driver' => 'agent_reach',
            'type' => 'agent_reach',
            'is_enabled' => true,
        ]);

        $this->artisan('opportunities:collect')->assertFailed();

        $this->assertDatabaseHas('source_runs', ['status' => 'failed']);
        $this->assertDatabaseHas('system_errors', ['level' => 'warning']);
        $this->assertDatabaseCount('opportunities', 0);
    }

    public function test_collection_normalizes_and_deduplicates_adapter_results(): void
    {
        $source = Source::factory()->create([
            'key' => 'fixture',
            'driver' => 'fixture',
            'type' => 'fixture',
            'is_enabled' => true,
        ]);

        app(SourceManager::class)->register(new class implements SourceAdapter
        {
            public function driver(): string
            {
                return 'fixture';
            }

            public function type(): string
            {
                return 'fixture';
            }

            public function collect(Source $source): CollectionResult
            {
                return new CollectionResult([
                    new RawOpportunity(
                        title: 'React Native dashboard',
                        company: 'Northwind',
                        sourceUrl: 'https://example.com/jobs/1?ref=tracker',
                        externalId: 'ext-1',
                        jobType: 'freelance',
                        workplace: 'remote',
                        budgetMin: '2000',
                        budgetMax: '4000',
                        currency: 'usd',
                        skills: ['React Native', 'Laravel'],
                        raw: ['id' => 'ext-1'],
                    ),
                ]);
            }
        });

        $this->artisan('opportunities:collect', ['--source' => 'fixture'])->assertSuccessful();
        $this->artisan('opportunities:collect', ['--source' => 'fixture'])->assertSuccessful();

        $this->assertSame(1, Opportunity::query()->count());

        $opportunity = Opportunity::query()->first();
        $this->assertSame($source->id, $opportunity->source_id);
        $this->assertSame('React Native dashboard', $opportunity->title);
        $this->assertSame('example.com/jobs/1', $opportunity->canonical_url);
        $this->assertSame('USD', $opportunity->currency);
        $this->assertSame('freelance', $opportunity->job_type->value);
        $this->assertEqualsCanonicalizing(
            ['laravel', 'react-native'],
            $opportunity->skills->pluck('slug')->all(),
        );
        $this->assertDatabaseHas('source_runs', [
            'source_id' => $source->id,
            'items_created' => 1,
        ]);
        $this->assertDatabaseHas('source_runs', [
            'source_id' => $source->id,
            'items_duplicated' => 1,
        ]);
    }

    public function test_source_failure_does_not_stop_other_sources(): void
    {
        Source::factory()->create([
            'key' => 'agent_reach',
            'driver' => 'agent_reach',
            'type' => 'agent_reach',
            'is_enabled' => true,
        ]);

        Source::factory()->create([
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
                        title: 'Still collected',
                        sourceUrl: 'https://example.com/jobs/ok',
                        externalId: 'ok-1',
                    ),
                ]);
            }
        });

        $this->artisan('opportunities:collect')->assertFailed();

        $this->assertDatabaseHas('source_runs', ['status' => 'failed']);
        $this->assertDatabaseHas('source_runs', ['status' => 'succeeded', 'items_created' => 1]);
        $this->assertSame(1, Opportunity::query()->count());
    }

    public function test_rss_collection_creates_opportunities_and_matches(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://example.com/feed.xml' => Http::response(
                file_get_contents(base_path('tests/fixtures/feeds/valid-rss.xml')),
                200,
            ),
        ]);

        $user = User::factory()->create();
        $user->profile->update([
            'keywords' => ['laravel'],
        ]);

        Source::factory()->rss('https://example.com/feed.xml')->create([
            'key' => 'example_rss',
            'is_enabled' => true,
        ]);

        $this->artisan('opportunities:collect', ['--source' => 'example_rss'])->assertSuccessful();

        $this->assertSame(2, Opportunity::query()->count());
        $this->assertDatabaseHas('opportunity_matches', [
            'user_id' => $user->id,
        ]);
        $this->assertGreaterThan(0, Opportunity::query()->whereNotNull('canonical_url')->count());
    }

    public function test_admin_can_enable_and_disable_a_source(): void
    {
        $admin = User::factory()->admin()->create();
        $source = Source::factory()->rss()->create(['is_enabled' => false]);

        $this->actingAs($admin)
            ->patch(route('admin.sources.update', $source), ['is_enabled' => '1'])
            ->assertRedirect(route('admin.sources.index'));

        $this->assertTrue($source->fresh()->is_enabled);

        $this->actingAs($admin)
            ->patch(route('admin.sources.update', $source), ['is_enabled' => '0'])
            ->assertRedirect();

        $this->assertFalse($source->fresh()->is_enabled);
    }
}
