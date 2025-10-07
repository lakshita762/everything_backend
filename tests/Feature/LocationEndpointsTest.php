<?php

use App\Models\LocationEntry;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lists only the authenticated users saved locations', function () {
    $user = User::factory()->create();
    $ownLocations = LocationEntry::factory()->count(3)->for($user)->create();
    LocationEntry::factory()->count(2)->create();

    Sanctum::actingAs($user);

    $payload = $this->getJson('/api/v1/locations')
        ->assertOk()
        ->json('data.locations');

    expect($payload)->toHaveCount(3);

    $ids = collect($payload)->pluck('id')->sort()->values()->all();
    $expected = $ownLocations->pluck('id')->sort()->values()->all();

    expect($ids)->toEqual($expected);
});

it('stores a new location entry with the provided label', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $coords = ['lat' => 37.7749, 'lng' => -122.4194, 'label' => 'SF Office'];

    $this->postJson('/api/v1/locations', $coords)
        ->assertCreated()
        ->assertJsonPath('data.location.label', 'SF Office')
        ->assertJsonPath('data.location.lat', 37.7749)
        ->assertJsonPath('data.location.lng', -122.4194);

    $this->assertDatabaseHas('location_entries', [
        'user_id' => $user->id,
        'title' => 'SF Office',
        'latitude' => 37.7749,
        'longitude' => -122.4194,
    ]);
});

it('defaults the label when none is provided', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/locations', [
        'lat' => 51.5074,
        'lng' => -0.1278,
    ])
        ->assertCreated()
        ->assertJsonPath('data.location.label', 'Pinned Location');

    $this->assertDatabaseHas('location_entries', [
        'user_id' => $user->id,
        'title' => 'Pinned Location',
    ]);
});

it('shows a single location entry the user owns', function () {
    $user = User::factory()->create();
    $location = LocationEntry::factory()->for($user)->create([
        'title' => 'Home',
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/v1/locations/{$location->id}")
        ->assertOk()
        ->assertJsonPath('data.location.id', $location->id)
        ->assertJsonPath('data.location.label', 'Home');
});

it('prevents viewing a location owned by someone else', function () {
    $user = User::factory()->create();
    $otherLocation = LocationEntry::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson("/api/v1/locations/{$otherLocation->id}")
        ->assertForbidden();
});

it('deletes a location entry the user owns', function () {
    $user = User::factory()->create();
    $location = LocationEntry::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/locations/{$location->id}")
        ->assertOk()
        ->assertJsonPath('data.message', 'Location removed');

    $this->assertDatabaseMissing('location_entries', [
        'id' => $location->id,
    ]);
});

it('does not allow deleting another users location', function () {
    $user = User::factory()->create();
    $otherLocation = LocationEntry::factory()->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/locations/{$otherLocation->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('location_entries', [
        'id' => $otherLocation->id,
    ]);
});

it('validates coordinates are required when storing a location', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/locations', [
        'lng' => 12.34,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('lat');
});
