<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;

class LiveSessionTest extends TestCase
{
    public function test_create_update_get_end_flow()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // create
        $res = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/v1/live-sessions', ['title' => 'Walk']);
        $res->assertStatus(201)->assertJsonStructure(['data' => ['session_id','title','created_at']]);
        $session_id = $res->json('data.session_id');

        // update
        $update = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/v1/live-sessions/{$session_id}/update", [
                'latitude' => 12.34,
                'longitude' => 56.78,
                'accuracy' => 5
            ]);
        $update->assertStatus(200)->assertJson(['status' => 'ok']);

        // get
        $get = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson("/api/v1/live-sessions/{$session_id}");
        $get->assertStatus(200)->assertJsonPath('data.latitude', 12.34);

        // end
        $end = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/v1/live-sessions/{$session_id}/end");
        $end->assertStatus(200)->assertJson(['status' => 'ended']);

        // get after end -> 410
        $get2 = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson("/api/v1/live-sessions/{$session_id}");
        $get2->assertStatus(410);
    }

    public function test_redis_publish_on_update()
    {
        Redis::shouldReceive('publish')->once();

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $res = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/v1/live-sessions', ['title' => 'Run']);
        $session_id = $res->json('data.session_id');

        $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/v1/live-sessions/{$session_id}/update", [
                'latitude' => 1.2,
                'longitude' => 3.4
            ])->assertStatus(200);
    }
}
