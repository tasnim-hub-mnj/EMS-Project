<?php

namespace App\Http\Resources;

use App\Models\StaffMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // استخراج أسماء الموظفين من IDs
        $assignedNames = [];
        if (is_array($this->assigned_staff_ids))
        {
            $assignedNames = StaffMember::whereIn('number', $this->assigned_staff_ids)
                ->pluck('name')
                ->toArray();
        }

        return
        [
            'id' => 't-' . $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'assignedTo' => $this->assigned_staff_ids ?? [],
            'assignedNames' => $assignedNames,
            'team' => $this->team,
            'status' => $this->status,
            'priority' => $this->priority,
            'dueDate' => $this->due_date,
            'exhibitionId' => (string) $this->exhibition_id,
        ];
    }
}
