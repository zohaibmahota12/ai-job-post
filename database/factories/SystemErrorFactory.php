<?php

namespace Database\Factories;

use App\Models\SystemError;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemError>
 */
class SystemErrorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level' => 'error',
            'message' => fake()->sentence(),
            'context' => null,
            'source_run_id' => null,
            'occurred_at' => now(),
        ];
    }
}
