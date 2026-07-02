<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExhibitionRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location' => 'required|string',
            'description' => 'nullable|string',
            'city' => 'nullable|string',
            'status' => 'nullable|string|in:far,upcoming,ongoing,finished',
            'copy_status' => 'nullable|string|in:draft,active,archived',
            'available_booths' => 'nullable|integer|min:0',
            'total_booths' => 'nullable|integer|min:0',
            'total_sponser_events' => 'nullable|integer|min:0',
            'visitors_count' => 'nullable|integer|min:0',
            'sectors' => 'nullable|array',
            'extra_services' => 'nullable|array',
            'working_hours'=>'nullable|numeric|min:0',
            'is_paid'=>'nullable|boolean',
            'ticket_price'=>'nullable|numeric|min:0',
            'map'=>'nullable|json|file'

        ];
    }
}
