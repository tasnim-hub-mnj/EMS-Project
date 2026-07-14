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
        return [
            
        'name'            => 'required|string|max:255',
        'type'            => 'required|string|max:255',
        'date'            => 'required|date',
        'time'            => 'required',
        'duration_days'   => 'required|integer|min:1',
        'description'     => 'nullable|string|max:500',

        'image'           => 'nullable|array',
        'image.*'         => 'image|mimes:jpg,jpeg,png|max:2048',

        'video_promo_url' => 'nullable|string|max:500',

        'is_general_invitation' => 'nullable|boolean',
        'has_bookable_seats'    => 'nullable|boolean',
        'requires_booking'      => 'nullable|boolean',

        'max_participants' => 'required|integer|min:1',
        'ticket_price'     => 'nullable|numeric|min:0',
        ];
    }
}

