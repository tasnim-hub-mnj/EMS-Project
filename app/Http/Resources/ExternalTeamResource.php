<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalTeamResource extends JsonResource
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
            'id' => 'ext' . $this->id,
            'name' => $this->name,
            'company' => $this->company,
            'role' => $this->role,
            'contactName' => $this->offical_name,
            'contactPhone' => $this->phone,
            'contactEmail' => $this->email,
            'contractValue' => $this->amount,
            'startDate' => $this->start_date,
            'endDate' => $this->end_date,
            'description' => $this->description,
            'status' => $this->status,
            'category' => $this->classification,
            'members' => ExternalTeamMemberResource::collection($this->externalTeamMembers),
            'tasks' => ExternalTeamTaskResource::collection($this->externalTeamTasks),
        ];
    }
}
