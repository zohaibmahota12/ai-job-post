<?php

namespace Tests\Unit;

use App\Models\Source;
use App\SourceHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_source_reports_disabled_health(): void
    {
        $source = Source::factory()->create([
            'is_enabled' => false,
            'consecutive_failures' => 0,
        ]);

        $this->assertSame(SourceHealth::Disabled, $source->health());
    }

    public function test_three_consecutive_failures_report_failing(): void
    {
        $source = Source::factory()->create([
            'is_enabled' => true,
            'consecutive_failures' => 3,
        ]);

        $this->assertSame(SourceHealth::Failing, $source->health());
    }

    public function test_single_failure_reports_warning(): void
    {
        $source = Source::factory()->create([
            'is_enabled' => true,
            'consecutive_failures' => 1,
            'last_error' => 'HTTP 503',
        ]);

        $this->assertSame(SourceHealth::Warning, $source->health());
    }

    public function test_clean_enabled_source_reports_healthy(): void
    {
        $source = Source::factory()->create([
            'is_enabled' => true,
            'consecutive_failures' => 0,
            'last_error' => null,
        ]);

        $this->assertSame(SourceHealth::Healthy, $source->health());
    }
}
