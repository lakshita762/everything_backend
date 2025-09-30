<?php

namespace Tests\Feature;

use App\Models\TodoList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_manage_tasks_after_accepting_invite(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();

        Sanctum::actingAs($owner);
        $listResponse = $this->postJson('/api/v1/todo-lists', [
            'name' => 'Shared Roadmap',
        ])->assertCreated();

        $listId = $listResponse->json('data.list.id');
        $this->assertEquals($owner->id, TodoList::findOrFail($listId)->owner_id);

        $invite = $this->postJson("/api/v1/todo-lists/{$listId}/share", [
            'email' => $editor->email,
            'role' => 'editor',
        ])->assertCreated();

        $inviteId = $invite->json('data.invite.id');

        Sanctum::actingAs($editor);
        $this->postJson("/api/v1/todo-lists/invites/{$inviteId}/accept")
            ->assertOk()
            ->assertJsonPath('data.invite.status', 'accepted');

        $this->postJson("/api/v1/todo-lists/{$listId}/tasks", [
            'title' => 'Draft requirements',
            'category' => 'planning',
        ])->assertCreated()
          ->assertJsonPath('data.task.title', 'Draft requirements');
    }

    public function test_viewer_cannot_create_tasks(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        Sanctum::actingAs($owner);
        $listId = $this->postJson('/api/v1/todo-lists', [
            'name' => 'Company Announcements',
        ])->assertCreated()->json('data.list.id');

        $inviteId = $this->postJson("/api/v1/todo-lists/{$listId}/share", [
            'email' => $viewer->email,
            'role' => 'viewer',
        ])->assertCreated()->json('data.invite.id');

        Sanctum::actingAs($viewer);
        $this->postJson("/api/v1/todo-lists/invites/{$inviteId}/accept")
            ->assertOk();

        $this->postJson("/api/v1/todo-lists/{$listId}/tasks", [
            'title' => 'Unauthorized task',
        ])->assertStatus(403);
    }

    public function test_invited_user_can_decline_and_invite_is_marked_declined(): void
    {
        $owner = User::factory()->create();
        $guest = User::factory()->create();

        Sanctum::actingAs($owner);
        $listId = $this->postJson('/api/v1/todo-lists', [
            'name' => 'Launch Checklist',
        ])->assertCreated()->json('data.list.id');

        $inviteId = $this->postJson("/api/v1/todo-lists/{$listId}/share", [
            'email' => $guest->email,
            'role' => 'viewer',
        ])->assertCreated()->json('data.invite.id');

        Sanctum::actingAs($guest);
        $this->postJson("/api/v1/todo-lists/invites/{$inviteId}/decline")
            ->assertOk()
            ->assertJsonPath('data.invite.status', 'declined');

        $this->assertDatabaseHas('todo_list_invites', [
            'id' => $inviteId,
            'status' => 'declined',
        ]);

        Sanctum::actingAs($owner);
        $ownerLists = $this->getJson('/api/v1/todo-lists?include=collaborators')
            ->assertOk()
            ->json('data.lists.0.collaborators');

        $this->assertCount(1, $ownerLists); // owner remains only collaborator
    }
}