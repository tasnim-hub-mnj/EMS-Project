<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        [
            'duration_days' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'services' => 'required|json',
            'services_products'=>'nullable|string|max:1000'
        ];
    }
}
