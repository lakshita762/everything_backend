<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LocationShareGroupMember */
class LocationShareGroupMemberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role,
            'user' => $this->relationLoaded('user') && $this->user
                ? new UserSummaryResource($this->user)
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
