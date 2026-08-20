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
            'exhibitionId' => $this->exhibition_id ? (string) $this->exhibition_id : null,
            'portalLinkId' => $this->portal_link_id ? (string) $this->portal_link_id : null,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'permissionKey' => $this->permission_key,
            'read' => $this->read,
            'createdAt' => $this->created_at->toISOString(),
            'data' => $this->data,
            'actionUrl' => $this->action_url,
        ];
    }
}
