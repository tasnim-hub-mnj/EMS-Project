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

            'daily_price' => 'nullable|numeric|min:0',
            'duration_options' => 'sometimes|array|min:1',
            'duration_options.*.days' => 'required_with:duration_options|integer|min:1',
            'duration_options.*.start_date' => 'required_with:duration_options|date_format:Y-m-d',
            'duration_options.*.end_date' => 'required_with:duration_options|date_format:Y-m-d|after_or_equal:duration_options.*.start_date',
            'duration_options.*.price' => 'required_with:duration_options|numeric|min:0',

            'max_participants' => 'sometimes|integer|min:1',

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
