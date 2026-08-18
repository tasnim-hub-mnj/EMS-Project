<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorEventProgramResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return
        [
            'id' => 'a' . $this->id,
            'title' => $this->title,
            'startTime' => $this->start_time,
            'endTime' => $this->end_time,
            'providerName' => $this->provider_name,
            'providerContact' => $this->provider_contact,
        ];
    }
}
