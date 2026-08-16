<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return
        [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:permanent,temporary',
            'role' => 'sometimes|string|max:255',
            'rank' => 'sometimes|string|max:255',
            'team' => 'sometimes|in:administrative,organizational,services',

            'nationalId' => 'sometimes|string|max:20',

            'schedule' => 'sometimes|string|max:255',
            'attendanceRate' => 'sometimes|numeric|min:0|max:100',
            'tasksCompleted' => 'sometimes|integer|min:0',
            'tasksTotal' => 'sometimes|integer|min:0',

            'salary' => 'sometimes|numeric|min:0',
            'paymentPeriod' => 'sometimes|in:monthly,bi-weekly,weekly,daily,hourly',

            'workDays' => 'sometimes|array',

            'idImage' => 'sometimes|image',
            'profileImage' => 'sometimes|image',
            'cvFile' => 'sometimes|file|mimes:pdf',
            'contractFile' => 'sometimes|file|mimes:pdf',
        ];

    }
}
