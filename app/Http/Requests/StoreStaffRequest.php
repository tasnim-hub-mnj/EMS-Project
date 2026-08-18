<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return
        [
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:50',

            'name' => 'required|string|max:255',
            'type' => 'nullable|in:permanent,temporary',
            'role' => 'nullable|string|max:255',
            'rank' => 'nullable|string|max:255',
            'team' => 'nullable|in:administrative,organizational,services',

            'nationalId' => 'nullable|string|max:20',

            'schedule' => 'nullable|string|max:255',
            'attendanceRate' => 'nullable|numeric|min:0|max:100',
            'tasksCompleted' => 'nullable|integer|min:0',
            'tasksTotal' => 'nullable|integer|min:0',

            'salary' => 'nullable|numeric|min:0',
            'paymentPeriod' => 'nullable|in:monthly,bi-weekly,weekly,daily,hourly',

            'workDays' => 'nullable|array',

            'idImage' => 'nullable|image',
            'profileImage' => 'nullable|image',
            'cvFile' => 'nullable|file|mimes:pdf',
            'contractFile' => 'nullable|file|mimes:pdf',
        ];
    }
}
