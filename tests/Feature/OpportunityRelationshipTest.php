<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_opportunity_belongs_to_a_source_and_many_skills(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create(['title' => 'API integration']);
        $skill = Skill::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        $opportunity->skills()->attach($skill);

        $this->assertTrue($opportunity->source->opportunities->contains($opportunity));
        $this->assertTrue($opportunity->fresh()->skills->contains($skill));
        $this->assertTrue($skill->fresh()->opportunities->contains($opportunity));

        $this->actingAs($user)
            ->get(route('opportunities.show', $opportunity))
            ->assertOk()
            ->assertSee('API integration')
            ->assertSee('Laravel');
    }
}
