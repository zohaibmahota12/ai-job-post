<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_their_own_profile(): void
    {
        $user = User::factory()->create(['name' => 'Before']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'After',
            'bio' => 'Builds Laravel products.',
            'experience' => 'Ten years shipping web apps.',
            'years_of_experience' => 10,
            'location' => 'Lisbon',
            'preferred_job_type' => 'freelance',
            'remote_preference' => 'remote',
            'minimum_budget' => 1500,
            'preferred_currency' => 'eur',
            'keywords' => 'laravel, remote',
            'excluded_keywords' => 'unpaid',
        ])->assertRedirect(route('profile.edit'));

        $profile = $user->fresh()->profile;
        $this->assertSame('After', $user->fresh()->name);
        $this->assertSame('Builds Laravel products.', $profile->bio);
        $this->assertSame(10, $profile->years_of_experience);
        $this->assertSame('Lisbon', $profile->location);
        $this->assertSame('freelance', $profile->preferred_job_type->value);
        $this->assertSame('remote', $profile->remote_preference->value);
        $this->assertSame('1500.00', $profile->minimum_budget);
        $this->assertSame('EUR', $profile->preferred_currency);
        $this->assertSame(['laravel', 'remote'], $profile->keywords);
        $this->assertSame(['unpaid'], $profile->excluded_keywords);
    }

    public function test_profile_update_does_not_change_another_users_profile(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $other->profile->update(['bio' => 'Leave this alone']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated',
            'bio' => 'Mine',
            'preferred_job_type' => 'any',
            'remote_preference' => 'any',
            'preferred_currency' => 'USD',
        ])->assertRedirect();

        $this->assertSame('Leave this alone', $other->fresh()->profile->bio);
    }
}
