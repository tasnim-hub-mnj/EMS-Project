<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSponsorEventRequest extends FormRequest
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
            'exhibition_id' => 'required|exists:exhibitions,id',

            'name' => 'required|string|max:255', // title
            'description' => 'nullable|string',
            'type' => 'required|string|max:100',

            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',

            'place' => 'required|string|max:255', // venue_name

            'ticket_type' => 'required|in:invitation,paid',
            'ticket_price' => 'required|numeric|min:0',

            'daily_price' => 'nullable|numeric|min:0',
            'duration_options' => 'required|array|min:1',
            'duration_options.*.days' => 'required|integer|min:1',
            'duration_options.*.start_date' => 'required|date_format:Y-m-d',
            'duration_options.*.end_date' => 'required|date_format:Y-m-d|after_or_equal:duration_options.*.start_date',
            'duration_options.*.price' => 'required|numeric|min:0',

            'max_participants' => 'required|integer|min:1',

            'activities' => 'nullable|array',
            'activities.*.title' => 'required|string',
            'activities.*.start_time' => 'required|string',
            'activities.*.end_time' => 'required|string',
            'activities.*.provider_name' => 'required|string',
            'activities.*.provider_contact' => 'nullable|string',

            'photos' => 'nullable|array',
            'photos.*.image' => 'required|string',
            'photos.*.caption' => 'nullable|string',
        ];
    }
}
