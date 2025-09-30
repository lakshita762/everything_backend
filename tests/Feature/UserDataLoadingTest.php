<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserDataLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_load_data_returns_todo_and_location_summaries(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $todoList = $this->postJson('/api/v1/todo-lists', [
            'name' => 'All Hands',
        ])->assertCreated();

        $this->postJson('/api/v1/location-shares', [
            'name' => 'City Walk',
            'allow_history' => true,
        ])->assertCreated();

        $response = $this->getJson('/api/v1/load-data')
            ->assertOk();

        $this->assertEquals('All Hands', $response->json('data.todo.lists.0.name'));
        $this->assertEquals('City Walk', $response->json('data.location.outgoing.0.name'));
    }
}