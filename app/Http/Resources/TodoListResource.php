<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/** @mixin \App\Models\TodoList */
class TodoListResource extends JsonResource
{
    public function toArray($request): array
    {
        $invites = $this->relationLoaded('invites')
            ? $this->invites
            : collect();

        if (!$invites instanceof Collection) {
            $invites = collect($invites);
        }

        $pendingInvites = $invites
            ->filter(fn ($invite) => $invite->status->value === 'pending')
            ->values();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'visibility' => $this->visibility->value,
            'owner' => $this->relationLoaded('owner')
                ? new UserSummaryResource($this->owner)
                : null,
            'tasks' => $this->relationLoaded('tasks')
                ? TodoTaskResource::collection($this->tasks)
                : [],
            'collaborators' => $this->relationLoaded('memberships')
                ? TodoListCollaboratorResource::collection($this->memberships)
                : [],
            'pending_invites' => TodoListInviteResource::collection($pendingInvites),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}