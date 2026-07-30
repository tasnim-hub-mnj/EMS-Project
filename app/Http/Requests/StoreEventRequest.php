<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
        [//✅
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'booth_id' => 'required|integer|exists:booths,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'time' => 'required|string',
            'max_participants' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'requires_booking' => 'boolean',
            'has_bookable_seats' => 'boolean',
            'total_seats' => 'nullable|integer|min:0',
            'ticket_price' => 'nullable|numeric|min:0',
            'is_general_invitation' => 'boolean',
            'video_promo_url' => 'nullable|string',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:4096'
        ];
    }
}

