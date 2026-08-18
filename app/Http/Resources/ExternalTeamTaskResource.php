<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalTeamTaskResource extends JsonResource
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
            'id' => 't' . $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'assignedTo' => $this->externalTeamMember->name,
            'dueDate' => $this->due_date,
        ];
    }
}
