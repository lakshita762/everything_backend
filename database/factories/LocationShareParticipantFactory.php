<?php

namespace Database\Factories;

use App\Enums\LocationParticipantRole;
use App\Enums\LocationParticipantStatus;
use App\Models\LocationShare;
use App\Models\LocationShareParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationShareParticipant>
 */
class LocationShareParticipantFactory extends Factory
{
    protected $model = LocationShareParticipant::class;

    public function definition(): array
    {
        return [
            'location_share_id' => LocationShare::factory(),
            'user_id' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => LocationParticipantRole::VIEWER,
            'status' => LocationParticipantStatus::PENDING,
            'invited_at' => now(),
            'responded_at' => null,
        ];
    }
}