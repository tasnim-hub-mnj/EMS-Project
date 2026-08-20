<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'exhibitionId' => (string) $this->exhibition_id,
            'name' => $this->name,
            'type' => $this->type,
            'width' => $this->width,
            'height' => $this->height,
            'mapX' => $this->map_x,
            'mapY' => $this->map_y,
            'metadata' => $this->metadata ?? [],
            'boothCount' => $this->whenLoaded('booths', fn () => $this->booths->count(), 0),
        ];
    }
}
