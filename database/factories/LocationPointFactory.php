<?php

namespace Database\Factories;

use App\Models\LocationPoint;
use App\Models\LocationShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationPoint>
 */
class LocationPointFactory extends Factory
{
    protected $model = LocationPoint::class;

    public function definition(): array
    {
        return [
            'location_share_id' => LocationShare::factory(),
            'user_id' => User::factory(),
            'lat' => fake()->latitude(),
            'lng' => fake()->longitude(),
            'recorded_at' => now(),
        ];
    }
}