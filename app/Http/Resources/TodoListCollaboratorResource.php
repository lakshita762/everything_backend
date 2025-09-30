<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TodoListMembership */
class TodoListCollaboratorResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'invited_at' => $this->invited_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
            ] : null,
        ];
    }
}