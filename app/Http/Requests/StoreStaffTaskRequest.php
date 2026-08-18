<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffTaskRequest extends FormRequest
{
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
