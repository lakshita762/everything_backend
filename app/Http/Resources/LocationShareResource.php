<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LocationShare */
class LocationShareResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'session_token' => $this->session_token,
            'allow_live_tracking' => (bool) $this->allow_live_tracking,
            'allow_history' => (bool) $this->allow_history,
            'status' => $this->status->value,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'owner' => $this->relationLoaded('owner') ? new UserSummaryResource($this->owner) : null,
            'participants' => $this->relationLoaded('participants')
                ? LocationShareParticipantResource::collection($this->participants)
                : [],
            'latest_point' => $this->relationLoaded('latestPoint') && $this->latestPoint
                ? new LocationPointResource($this->latestPoint)
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}