<?php

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver' => fake()->unique()->slug(2),
            'name' => fake()->company(),
            'description' => fake()->sentence(),
            'is_enabled' => false,
            'config' => null,
        ];
    }
}
