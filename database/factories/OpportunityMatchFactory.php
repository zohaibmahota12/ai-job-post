<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\OpportunityMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpportunityMatch>
 */
class OpportunityMatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'opportunity_id' => Opportunity::factory(),
            'score' => null,
            'reasons' => null,
            'matched_at' => null,
        ];
    }
}
