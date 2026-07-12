<?php

namespace Database\Factories;

use App\Enums\ResourceType;
use App\Models\Observation;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<resource>
 */
class ResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resourceable_id' => Observation::factory(),
            'resourceable_type' => Observation::class,
            'type' => ResourceType::Photo->value,
            'path' => 'observations/1/'.$this->faker->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => $this->faker->numberBetween(100_000, 5_000_000),
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the resource is a video.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ResourceType::Video->value,
            'path' => 'observations/1/'.$this->faker->uuid().'.mp4',
            'mime_type' => 'video/mp4',
            'size_bytes' => $this->faker->numberBetween(5_000_000, 50_000_000),
        ]);
    }
}
