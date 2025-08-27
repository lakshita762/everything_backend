<?php

it('performs todo crud', function () {
    $reg = $this->postJson('/api/v1/register', [
        'name'=>'A','email'=>'a@a.com','password'=>'password'
    ])->json();
    $token = $reg['data']['token'];

    $create = $this->withHeader('Authorization', "Bearer $token")
        ->postJson('/api/v1/todos', ['title'=>'Task 1','category'=>'Work'])
        ->assertCreated()->json();

    $id = $create['data']['id'];

    $this->withHeader('Authorization', "Bearer $token")
        ->get('/api/v1/todos')->assertOk();

    $this->withHeader('Authorization', "Bearer $token")
        ->putJson("/api/v1/todos/$id", ['is_done'=>true])->assertOk();

    $this->withHeader('Authorization', "Bearer $token")
        ->delete("/api/v1/todos/$id")->assertOk();
});
