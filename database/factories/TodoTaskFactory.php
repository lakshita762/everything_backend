<?php

namespace Database\Factories;

use App\Models\TodoList;
use App\Models\TodoTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TodoTask>
 */
class TodoTaskFactory extends Factory
{
    protected $model = TodoTask::class;

    public function definition(): array
    {
        return [
            'todo_list_id' => TodoList::factory(),
            'title' => fake()->sentence(4),
            'category' => fake()->randomElement(['work', 'personal', 'home']),
            'is_done' => false,
            'due_at' => now()->addDays(fake()->numberBetween(1, 10)),
            'assigned_to' => null,
        ];
    }
}