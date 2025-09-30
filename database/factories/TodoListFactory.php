<?php

namespace Database\Factories;

use App\Enums\TodoListVisibility;
use App\Models\TodoList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TodoList>
 */
class TodoListFactory extends Factory
{
    protected $model = TodoList::class;

    public function definition(): array
    {
        $name = fake()->sentence(3);

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name . '-' . fake()->unique()->lexify('????')), 
            'visibility' => TodoListVisibility::PRIVATE,
        ];
    }

    public function shared(): static
    {
        return $this->state(fn () => [
            'visibility' => TodoListVisibility::SHARED,
        ]);
    }
}