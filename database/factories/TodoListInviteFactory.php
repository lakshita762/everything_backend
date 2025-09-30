<?php

namespace Database\Factories;

use App\Enums\TodoListInviteStatus;
use App\Enums\TodoListRole;
use App\Models\TodoList;
use App\Models\TodoListInvite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TodoListInvite>
 */
class TodoListInviteFactory extends Factory
{
    protected $model = TodoListInvite::class;

    public function definition(): array
    {
        return [
            'todo_list_id' => TodoList::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => TodoListRole::VIEWER,
            'token' => Str::uuid()->toString(),
            'status' => TodoListInviteStatus::PENDING,
            'expires_at' => now()->addDays(7),
            'invited_at' => now(),
        ];
    }
}