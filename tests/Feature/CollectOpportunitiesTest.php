<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Source;
use App\Sources\CollectionResult;
use App\Sources\RawOpportunity;
use App\Sources\SourceAdapter;
use App\Sources\SourceManager;
use Database\Seeders\SourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    }

    public function test_disabled_source_is_skipped_without_creating_listings(): void
    {
        Source::factory()->create([
            'driver' => 'agent_reach',
            'is_enabled' => false,
        ]);

        $this->artisan('opportunities:collect')->assertSuccessful();

        $this->assertDatabaseHas('source_runs', ['status' => 'skipped']);
        $this->assertDatabaseCount('opportunities', 0);
    }

    public function test_enabled_agent_reach_source_fails_without_inventing_listings(): void
    {
        Source::factory()->create([
            'driver' => 'agent_reach',
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
            'driver' => 'fixture',
            'is_enabled' => true,
        ]);

        app(SourceManager::class)->register(new class implements SourceAdapter
        {
            public function driver(): string
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
            'items_skipped' => 1,
        ]);
    }
}
