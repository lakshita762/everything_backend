<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns todo and location summaries when loading data', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);
    $this->postJson('/api/v1/todo-lists', [
        'name' => 'All Hands',
    ])->assertCreated();

    $this->postJson('/api/v1/location-shares', [
        'name' => 'City Walk',
        'allow_history' => true,
    ])->assertCreated();

    $response = $this->getJson('/api/v1/load-data')
        ->assertOk();

    expect($response->json('data.todo.lists.0.name'))->toBe('All Hands');
    expect($response->json('data.location.outgoing.0.name'))->toBe('City Walk');
});
