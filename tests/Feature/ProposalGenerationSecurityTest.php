<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProposalGenerationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_proposal_generation_is_rate_limited_per_user(): void
    {
        Config::set('ai.enabled', true);
        Config::set('ai.rate_limit_per_minute', 1);
        Config::set('ai.providers.openai.api_key', 'test-key');
        Config::set('ai.providers.openai.base_url', 'https://api.openai.com/v1');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['proposal' => 'Draft']),
                    ],
                ]],
            ]),
        ]);

        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create();

        $this->actingAs($user)
            ->post(route('opportunities.proposals.generate', $opportunity))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('opportunities.proposals.generate', $opportunity))
            ->assertStatus(429);
    }

    public function test_admin_views_do_not_expose_ai_api_key(): void
    {
        Config::set('ai.enabled', true);
        Config::set('ai.providers.openai.api_key', 'super-secret-ai-key');

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.sources.index'))
            ->assertOk()
            ->assertDontSee('super-secret-ai-key');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('super-secret-ai-key');
    }
}
