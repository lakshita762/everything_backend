<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LocationEntry */
class LocationEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->title,
            'tag' => $this->tag ?? $this->title,
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
