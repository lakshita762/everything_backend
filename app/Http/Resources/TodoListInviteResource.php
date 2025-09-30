<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TodoListInvite */
class TodoListInviteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'token' => $this->token,
            'invited_at' => $this->invited_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'list' => $this->relationLoaded('list')
                ? [
                    'id' => $this->list->id,
                    'name' => $this->list->name,
                    'slug' => $this->list->slug,
                    'owner' => $this->list->relationLoaded('owner')
                        ? new UserSummaryResource($this->list->owner)
                        : null,
                ]
                : null,
        ];
    }
}