<?php

namespace Database\Factories;

use App\JobType;
use App\Models\Opportunity;
use App\Models\Source;
use App\OpportunityStatus;
use App\Workplace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'company' => fake()->company(),
            'source_url' => fake()->url(),
            'external_id' => fake()->unique()->uuid(),
            'location' => fake()->city(),
            'job_type' => JobType::Freelance,
            'workplace' => Workplace::Remote,
            'budget_min' => 1000,
            'budget_max' => 3000,
            'currency' => 'USD',
            'posted_at' => now()->subDay(),
            'deadline_at' => now()->addWeek(),
            'required_experience_years' => 3,
            'raw_data' => ['origin' => 'factory'],
            'normalized_data' => ['origin' => 'factory'],
            'status' => OpportunityStatus::Open,
            'content_hash' => hash('sha256', fake()->unique()->uuid()),
        ];
    }
}
