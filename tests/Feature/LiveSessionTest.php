<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('allows a tracker to push live updates and stream data', function () {
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

    expect($stream->streamedContent())->toContain('12.345678');
});

it('prevents viewers from pushing live updates', function () {
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
    ])->assertForbidden();
});

it('shows pending invites in the location share index', function () {
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

    expect($index->json('data.invites'))->not()->toBeEmpty();
    expect($index->json('data.invites.0.status'))->toBe('pending');
});
