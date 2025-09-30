<?php

namespace Database\Factories;

use App\Enums\LocationShareStatus;
use App\Models\LocationShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LocationShare>
 */
class LocationShareFactory extends Factory
{
    protected $model = LocationShare::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->words(3, true),
            'session_token' => Str::uuid()->toString(),
            'allow_live_tracking' => true,
            'allow_history' => true,
            'status' => LocationShareStatus::ACTIVE,
            'expires_at' => now()->addHours(6),
        ];
    }
}