<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
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
            'amount' => fake()->randomFloat(2, 1, 1000),
            'category' => fake()->randomElement(['food', 'transport', 'entertainment', 'shopping', 'bills', 'health']),
            'date' => fake()->dateTimeBetween('-30 days', 'now'),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the expense is from today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now(),
        ]);
    }

    /**
     * Indicate that the expense is from this week.
     */
    public function thisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
