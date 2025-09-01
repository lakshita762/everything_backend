<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Todo;
use App\Models\Expense;
use App\Models\LocationEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDataLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_data_is_loaded_on_login()
    {
        // Create a user with some data
        $user = User::factory()->create();
        
        // Create some todos, expenses, and location entries for the user
        Todo::factory()->count(3)->create(['user_id' => $user->id]);
        Expense::factory()->count(2)->create(['user_id' => $user->id]);
        LocationEntry::factory()->count(1)->create(['user_id' => $user->id]);

        // Attempt to login
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password', // Assuming this is the default password
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'token',
                         'user' => [
                             'id',
                             'name',
                             'email',
                             'todos',
                             'expenses',
                             'location_entries'
                         ]
                     ]
                 ]);

        // Verify that user data is loaded
        $responseData = $response->json('data.user');
        $this->assertCount(3, $responseData['todos']);
        $this->assertCount(2, $responseData['expenses']);
        $this->assertCount(1, $responseData['location_entries']);
    }

    public function test_user_data_is_loaded_on_me_endpoint()
    {
        // Create a user with some data
        $user = User::factory()->create();
        
        // Create some data for the user
        Todo::factory()->count(2)->create(['user_id' => $user->id]);
        Expense::factory()->count(3)->create(['user_id' => $user->id]);
        LocationEntry::factory()->count(2)->create(['user_id' => $user->id]);

        // Login to get token
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        // Call the me endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/me');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user' => [
                             'id',
                             'name',
                             'email',
                             'todos',
                             'expenses',
                             'location_entries'
                         ]
                     ]
                 ]);

        // Verify that user data is loaded
        $responseData = $response->json('data.user');
        $this->assertCount(2, $responseData['todos']);
        $this->assertCount(3, $responseData['expenses']);
        $this->assertCount(2, $responseData['location_entries']);
    }

    public function test_load_data_endpoint_works()
    {
        // Create a user with some data
        $user = User::factory()->create();
        
        // Create some data for the user
        Todo::factory()->count(1)->create(['user_id' => $user->id]);
        Expense::factory()->count(1)->create(['user_id' => $user->id]);
        LocationEntry::factory()->count(1)->create(['user_id' => $user->id]);

        // Login to get token
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        // Call the load-data endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/load-data');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user' => [
                             'id',
                             'name',
                             'email',
                             'todos',
                             'expenses',
                             'location_entries'
                         ]
                     ]
                 ]);

        // Verify that user data is loaded
        $responseData = $response->json('data.user');
        $this->assertCount(1, $responseData['todos']);
        $this->assertCount(1, $responseData['expenses']);
        $this->assertCount(1, $responseData['location_entries']);
    }

    public function test_data_is_ordered_by_latest_first()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create todos with different timestamps
        $oldTodo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'Old Todo',
            'created_at' => now()->subDays(2)
        ]);
        
        $newTodo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'New Todo',
            'created_at' => now()
        ]);

        // Login
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        // Verify that the newest todo comes first
        $todos = $response->json('data.user.todos');
        $this->assertEquals('New Todo', $todos[0]['title']);
        $this->assertEquals('Old Todo', $todos[1]['title']);
    }
}
