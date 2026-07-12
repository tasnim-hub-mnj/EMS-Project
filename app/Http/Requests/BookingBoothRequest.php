<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookingBoothRequest extends FormRequest
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
        [//BookingBoothRequest
            'start_date' =>
            [
                'required',
                'date',
                Rule::afterOrEqual($this->exhibition_start),
                Rule::beforeOrEqual($this->exhibition_end),
            ],
            'duration_days' => 'required|integer|min:1',
            'additional_services' => 'nullable|json',
            'notes' => 'nullable|string',
            'services_products'=>'nullable|string|max:1000'
        ];
    }
}
