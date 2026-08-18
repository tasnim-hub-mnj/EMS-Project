<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSponsorEventRequest extends FormRequest
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
    {//o
        return
        [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|nullable',
            'type' => 'sometimes|string|max:100',

            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after_or_equal:start_time',

            'place' => 'sometimes|string|max:255',

            'ticket_type' => 'sometimes|in:invitation,paid',
            'ticket_price' => 'sometimes|numeric|min:0',

            'max_participants' => 'sometimes|integer|min:1',

            'activities' => 'nullable|array',
            'photos' => 'nullable|array',
        ];
    }
}
