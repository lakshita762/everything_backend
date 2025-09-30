<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LocationPoint */
class LocationPointResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'recorded_at' => $this->recorded_at?->toIso8601String(),
            'user' => $this->relationLoaded('user') && $this->user ? new UserSummaryResource($this->user) : null,
        ];
    }
}