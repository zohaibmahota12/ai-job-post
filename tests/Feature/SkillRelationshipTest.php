<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_keep_separate_skill_lists_that_share_normalized_skills(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($first)->post(route('profile.skills.store'), ['name' => 'React Native']);
        $this->actingAs($first)->post(route('profile.skills.store'), ['name' => 'Laravel']);
        $this->actingAs($first)->post(route('profile.skills.store'), ['name' => 'laravel']);
        $this->actingAs($second)->post(route('profile.skills.store'), ['name' => 'Python']);
        $this->actingAs($second)->post(route('profile.skills.store'), ['name' => 'Laravel']);

        $this->assertSame(3, Skill::query()->count());
        $this->assertEqualsCanonicalizing(
            ['laravel', 'react-native'],
            $first->fresh()->skills->pluck('slug')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['laravel', 'python'],
            $second->fresh()->skills->pluck('slug')->all(),
        );

        $this->actingAs($first)
            ->delete(route('profile.skills.destroy', Skill::query()->where('slug', 'laravel')->first()))
            ->assertRedirect();

        $this->assertEqualsCanonicalizing(['react-native'], $first->fresh()->skills->pluck('slug')->all());
        $this->assertTrue($second->fresh()->skills->contains('slug', 'laravel'));
    }
}
