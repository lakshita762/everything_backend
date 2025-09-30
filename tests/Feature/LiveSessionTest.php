<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LiveSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracker_can_push_live_updates_and_stream_data(): void
    {
        $owner = User::factory()->create();
        $tracker = User::factory()->create();

        Sanctum::actingAs($owner);
        $shareResponse = $this->postJson('/api/v1/location-shares', [
            'name' => 'Morning Run',
            'allow_history' => true,
        ])->assertCreated();

        $shareId = $shareResponse->json('data.share.id');
        $sessionToken = $shareResponse->json('data.share.session_token');

        $participant = $this->postJson("/api/v1/location-shares/{$shareId}/participants", [
            'email' => $tracker->email,
            'role' => 'tracker',
        ])->assertCreated();

        $participantId = $participant->json('data.participant.id');

        Sanctum::actingAs($tracker);
        $this->postJson("/api/v1/location-shares/invites/{$participantId}/accept")
            ->assertOk()
            ->assertJsonPath('data.participant.status', 'accepted');

        $this->postJson('/api/v1/locations/live', [
            'share_id' => $shareId,
            'lat' => 12.345678,
            'lng' => 98.765432,
        ])->assertCreated();

        $stream = $this->withHeader('Accept', 'text/event-stream')
            ->get("/api/v1/locations/live/{$sessionToken}")
            ->assertOk();

        $this->assertStringContainsString('12.345678', $stream->streamedContent());
    }

    public function test_viewer_cannot_push_live_updates(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        Sanctum::actingAs($owner);
        $shareId = $this->postJson('/api/v1/location-shares', [
            'name' => 'Family Trip',
        ])->assertCreated()->json('data.share.id');

        $participantId = $this->postJson("/api/v1/location-shares/{$shareId}/participants", [
            'email' => $viewer->email,
            'role' => 'viewer',
        ])->assertCreated()->json('data.participant.id');

        Sanctum::actingAs($viewer);
        $this->postJson("/api/v1/location-shares/invites/{$participantId}/accept")
            ->assertOk();

        $this->postJson('/api/v1/locations/live', [
            'share_id' => $shareId,
            'lat' => 1,
            'lng' => 1,
        ])->assertStatus(403);
    }

    public function test_pending_invite_appears_in_location_share_index(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();

        Sanctum::actingAs($owner);
        $shareId = $this->postJson('/api/v1/location-shares', [
            'name' => 'Ski Weekend',
        ])->assertCreated()->json('data.share.id');

        $this->postJson("/api/v1/location-shares/{$shareId}/participants", [
            'email' => $invitee->email,
            'role' => 'viewer',
        ])->assertCreated();

        Sanctum::actingAs($invitee);
        $index = $this->getJson('/api/v1/location-shares')
            ->assertOk();

        $this->assertNotEmpty($index->json('data.invites'));
        $this->assertEquals('pending', $index->json('data.invites.0.status'));
    }
}