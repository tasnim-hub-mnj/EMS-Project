<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\StaffMember;
use App\Models\PortalLink;

class StoreStaffTaskRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $exhibitionId = $user?->organizer()->first()?->exhibition()->first()?->id
            ?? PortalLink::query()
                ->where('token', $this->header('X-Portal-Token'))
                ->where('active', true)
                ->value('exhibition_id');
        $assignedStaffIds = $this->input('assigned_staff_ids', $this->input('assigned_to', []));
        $assignedStaffIds = is_array($assignedStaffIds) ? $assignedStaffIds : [$assignedStaffIds];

        $team = $this->input('team');
        if (!$team && !empty($assignedStaffIds)) {
            $team = StaffMember::where('exhibition_id', $exhibitionId)
                ->whereIn('number', $assignedStaffIds)
                ->value('team');
        }
        $team = $team === 'service' ? 'services' : $team;

        $this->merge([
            'exhibition_id' => $exhibitionId,
            'assigned_staff_ids' => array_values(array_filter($assignedStaffIds)),
            'team' => $team,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return
        [
            'exhibition_id' => 'required|exists:exhibitions,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',

            'team' => 'required|in:administrative,technical,services,organizational,security',

            'priority' => 'required|in:low,medium,high',
            'status' => 'nullable|in:pending,in_progress,completed,delayed',

            'due_date' => 'nullable|date',

            'assigned_staff_ids' => 'nullable|array',
            'assigned_staff_ids.*' => 'string|exists:staff_members,number',
        ];
    }
}
