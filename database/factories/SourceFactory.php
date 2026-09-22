<?php

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $key = fake()->unique()->slug(2);

        return [
            'key' => $key,
            'driver' => $key,
            'type' => $key,
            'name' => fake()->company(),
            'description' => fake()->sentence(),
            'is_enabled' => false,
            'config' => null,
        ];
    }

    public function rss(?string $url = null): static
    {
        return $this->state(fn (): array => [
            'key' => 'rss_'.Str::lower(Str::random(8)),
            'driver' => 'rss',
            'type' => 'rss',
            'name' => 'RSS Feed',
            'config' => [
                'url' => $url ?? 'https://example.com/feed.xml',
                'rate_limit_hint' => 'Respect feed publisher rate limits; default collection is once per day.',
            ],
        ]);
    }

    public function jsonApi(?string $url = null): static
    {
        return $this->state(fn (): array => [
            'key' => 'json_'.Str::lower(Str::random(8)),
            'driver' => 'json_api',
            'type' => 'json_api',
            'name' => 'JSON Job API',
            'config' => [
                'url' => $url ?? 'https://example.com/jobs.json',
                'items_path' => 'jobs',
                'rate_limit_hint' => 'Use only public credential-free endpoints permitted by the provider.',
            ],
        ]);
    }
}
