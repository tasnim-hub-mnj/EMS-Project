<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return
        [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',

            'team' => 'sometimes|in:administrative,technical,services,organizational,security',

            'priority' => 'sometimes|in:low,medium,high',
            'status' => 'sometimes|in:pending,in_progress,completed,delayed',

            'due_date' => 'sometimes|date',

            'assigned_staff_ids' => 'sometimes|array',
            'assigned_staff_ids.*' => 'string|exists:staff_members,number',
        ];
    }
}
