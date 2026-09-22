<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\Proposal;
use App\Models\User;
use App\ProposalStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
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
            'subject' => null,
            'content' => fake()->paragraph(),
            'status' => ProposalStatus::Draft,
            'generated_by_ai' => false,
        ];
    }
}
