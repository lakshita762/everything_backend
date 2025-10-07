<?php

use App\Models\User;
use Google\Client as GoogleClient;
use Laravel\Sanctum\Sanctum;

afterEach(function () {
    Mockery::close();
});

it('allows a user to register, login, and logout', function () {
    $register = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
    ])->assertCreated();

    $token = $register->json('data.token');
    expect($token)->not()->toBeEmpty();

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.email', 'test@example.com');

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ])->assertOk();

    expect($login->json('data.token'))->not()->toBeEmpty();

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('data.message', 'Logged out');
});

it('upserts a user when logging in via Google', function () {
    config()->set('services.google.client_id', 'test-client');

    $mock = Mockery::mock(GoogleClient::class);
    $mock->shouldReceive('setClientId')->once()->with('test-client');
    $mock->shouldReceive('verifyIdToken')
        ->once()
        ->with('valid-token')
        ->andReturn([
            'aud' => 'test-client',
            'iss' => 'https://accounts.google.com',
            'email_verified' => true,
            'sub' => 'google-123',
            'email' => 'google@example.com',
            'name' => 'Google Person',
            'picture' => 'https://example.com/avatar.png',
        ]);

    app()->instance(GoogleClient::class, $mock);

    $response = $this->postJson('/api/v1/auth/google', [
        'id_token' => 'valid-token',
    ])->assertOk();

    $response->assertJsonPath('data.user.email', 'google@example.com');
    $this->assertDatabaseHas('users', [
        'email' => 'google@example.com',
        'google_id' => 'google-123',
    ]);
});
