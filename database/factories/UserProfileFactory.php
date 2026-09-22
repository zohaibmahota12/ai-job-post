<?php

namespace Database\Factories;

use App\JobType;
use App\Models\User;
use App\Models\UserProfile;
use App\RemotePreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fn (): int => User::withoutEvents(fn (): User => User::factory()->create())->id,
            'bio' => fake()->paragraph(),
            'experience' => fake()->paragraph(),
            'years_of_experience' => fake()->numberBetween(0, 20),
            'location' => fake()->city(),
            'preferred_job_type' => JobType::Freelance,
            'remote_preference' => RemotePreference::Remote,
            'minimum_budget' => fake()->numberBetween(500, 5000),
            'preferred_currency' => 'USD',
            'keywords' => ['remote', 'laravel'],
            'excluded_keywords' => ['unpaid'],
        ];
    }
}
