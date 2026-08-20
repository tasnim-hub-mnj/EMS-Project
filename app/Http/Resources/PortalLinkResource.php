<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortalLinkResource extends JsonResource
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
            'token' => $this->token,
            'role' => $this->role,
            'permissions' => $this->permissions ?? [],
            'messagingChannels' => $this->messaging_channels ?? [],

            'staffName' => $this->staff_name,
            'staffEmail' => $this->staff_email,
            'staffTitle' => $this->staff_title,
            'staffId' => $this->staff_id,
            'firebaseUid' => $this->firebase_uid ?? ('staff:' . ($this->staff?->user_id ?? '')),
            'staffNumber' => $this->staff_number,

            'isManager' => $this->is_manager,

            'createdBy' => $this->created_by,
            'createdByName' => $this->created_by_name,

            'parentToken' => $this->parent_token,

            'exhibitionId' => (string) $this->exhibition_id,
            'exhibitionName' => $this->exhibition_name,

            'createdAt' => $this->created_at->toISOString(),
            'active' => $this->active,
        ];
    }
}
