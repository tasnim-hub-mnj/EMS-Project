<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return 
        [
            'investor_id' => 'required|exists:investors,id',
            'booth_id' => 'required|exists:booths,id',

            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',

            'additional_services' => 'nullable|array',
            'additional_services.*' => 'string',

            'service_prices' => 'nullable|array',
            'service_prices.*' => 'numeric|min:0',

            'notes' => 'nullable|string|max:500',
        ];
    }
}

