<?php

namespace Tests\Unit;

use App\Models\Source;
use App\Sources\JsonApiSourceAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JsonApiSourceAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_maps_fixture_json_payload(): void
    {
        $source = Source::factory()->jsonApi()->create([
            'config' => [
                'url' => 'https://jobs.example.com/jobs.json',
                'items_path' => 'jobs',
            ],
        ]);

        $payload = json_decode(file_get_contents(base_path('tests/fixtures/feeds/jobs.json')), true);
        $items = app(JsonApiSourceAdapter::class)->mapPayload($payload, $source);

        $this->assertCount(2, $items);
        $this->assertSame('Public API Role', $items[0]->title);
        $this->assertSame('json-1', $items[0]->externalId);
        $this->assertSame('Example Corp', $items[0]->company);
        $this->assertSame(['Laravel', 'PHP'], $items[0]->skills);
        $this->assertSame('2000', $items[0]->budgetMin);
        $this->assertSame('USD', $items[0]->currency);
    }
}
