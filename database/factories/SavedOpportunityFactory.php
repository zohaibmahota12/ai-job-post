<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\SavedOpportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedOpportunity>
 */
class SavedOpportunityFactory extends Factory
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
            'notes' => null,
        ];
    }
}
