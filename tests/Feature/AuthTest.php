<?php

it('registers, logins and returns me', function () {
    $reg = $this->postJson('/api/v1/register', [
        'name'=>'Test', 'email'=>'test@example.com', 'password'=>'password'
    ])->assertStatus(201)->json();

    $token = $reg['data']['token'];

    $this->withHeader('Authorization', "Bearer $token")
        ->get('/api/v1/me')->assertOk();

    $login = $this->postJson('/api/v1/login', [
        'email'=>'test@example.com', 'password'=>'password'
    ])->assertOk()->json();

    expect($login['data']['token'])->not->toBeEmpty();
});
