<?php

namespace Database\Factories;

use App\Models\Observation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Observation>
 */
class ObservationFactory extends Factory
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
            'species' => $this->faker->randomElement([
                'Chelonia mydas',
                'Tursiops truncatus',
                'Hippocampus kuda',
                'Manta birostris',
                'Octopus vulgaris',
                'Rhincodon typus',
                'Delphinapterus leucas',
            ]),
            'observed_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'location_name' => $this->faker->city().' '.$this->faker->randomElement(['Reef', 'Bay', 'Lagoon', 'Coast', 'Harbor']),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'water_temperature' => $this->faker->optional(0.5)->randomFloat(1, 5, 35),
            'depth_meters' => $this->faker->optional(0.5)->randomFloat(1, 0.5, 60),
            'weather' => $this->faker->optional(0.4)->randomElement(['Sunny', 'Cloudy', 'Overcast', 'Rainy', 'Windy', 'Calm']),
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the observation is unpublished (draft).
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }
}
