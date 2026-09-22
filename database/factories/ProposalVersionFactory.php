<?php

namespace Database\Factories;

use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\ProposalVersionSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProposalVersion>
 */
class ProposalVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'content' => fake()->paragraph(),
            'subject' => fake()->optional()->sentence(4),
            'source' => ProposalVersionSource::User,
        ];
    }
}
