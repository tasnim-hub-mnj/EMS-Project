<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorEventImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $image = (string) $this->image;

        return [
            'id' => 'p' . $this->id,
            'url' => str_starts_with($image, 'data:') || str_starts_with($image, 'http://') || str_starts_with($image, 'https://')
                ? $image
                : asset('storage/' . ltrim($image, '/')),
            'caption' => $this->caption,
        ];
    }
}
