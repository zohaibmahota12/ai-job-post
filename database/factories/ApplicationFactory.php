<?php

namespace Database\Factories;

use App\ApplicationStatus;
use App\Models\Application;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
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
            'proposal_id' => null,
            'status' => ApplicationStatus::New,
            'notes' => null,
            'contact_name' => null,
            'follow_up_at' => null,
            'external_url' => null,
            'applied_at' => null,
        ];
    }
}
