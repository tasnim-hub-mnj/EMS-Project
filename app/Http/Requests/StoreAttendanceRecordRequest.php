<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRecordRequest extends FormRequest
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
            'staff_id' => 'required|exists:staff_members,id',

            'type' => 'required|in:administrative,technical,services,organizational,security',

            'date' => 'required|date',

            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',

            'hours_worked' => 'nullable|numeric|min:0',

            'method' => 'required|in:QR,manual',
        ];
    }
}
