<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExternalTeamRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'offical_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'classification' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,active,finished',

            // أعضاء الفريق
            'members' => 'nullable|array',
            'members.*.name' => 'required_with:members|string|max:255',
            'members.*.role' => 'required_with:members|string|max:255',
            'members.*.phone' => 'nullable|string|max:50',
            'members.*.email' => 'nullable|email',

            // المهام
            'tasks' => 'nullable|array',
            'tasks.*.title' => 'required_with:tasks|string|max:255',
            'tasks.*.external_team_member_id' => 'required_with:tasks|exists:external_team_members,id',
            'tasks.*.due_date' => 'nullable|date',
            'tasks.*.status' => 'nullable|in:pending,in_progress,completed,delayed',
        ];
    }
}
