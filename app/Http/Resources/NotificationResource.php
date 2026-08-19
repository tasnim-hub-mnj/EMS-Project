<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
                'id' => $this->id,
                'userId' => $this->user_id,
                'title' => $this->title,
                'body' => $this->body,
                'type' => $this->type,
                'read' => $this->read,
                'createdAt' => $this->created_at?->toISOString(),
                'data' => $this->data,
                'actionUrl' => $this->action_url,
            ];
    }
}
