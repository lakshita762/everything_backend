<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LocationShareParticipant */
class LocationShareParticipantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'invited_at' => $this->invited_at?->toIso8601String(),
            'responded_at' => $this->responded_at?->toIso8601String(),
            'user' => $this->relationLoaded('user') && $this->user ? new UserSummaryResource($this->user) : null,
            'share' => $this->relationLoaded('share') && $this->share ? [
                'id' => $this->share->id,
                'name' => $this->share->name,
                'session_token' => $this->share->session_token,
                'status' => $this->share->status->value,
                'owner' => $this->share->relationLoaded('owner') ? new UserSummaryResource($this->share->owner) : null,
            ] : null,
        ];
    }
}