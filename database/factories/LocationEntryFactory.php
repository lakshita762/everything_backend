<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LocationEntry>
 */
class LocationEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(2, true),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Create a location entry for a specific city.
     */
    public function forCity(string $city): static
    {
        return $this->state(function (array $attributes) use ($city) {
            // Generate coordinates for common cities
            $cities = [
                'New York' => [40.7128, -74.0060],
                'London' => [51.5074, -0.1278],
                'Tokyo' => [35.6762, 139.6503],
                'Paris' => [48.8566, 2.3522],
                'Sydney' => [-33.8688, 151.2093],
            ];

            if (isset($cities[$city])) {
                return [
                    'title' => $city,
                    'latitude' => $cities[$city][0],
                    'longitude' => $cities[$city][1],
                ];
            }

            return [
                'title' => $city,
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
            ];
        });
    }
}
