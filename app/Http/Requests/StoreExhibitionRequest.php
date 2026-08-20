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
            'name' => 'nullable|string|max:255',
            'location' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'working_hours'=>'nullable|numeric|min:0',
            'total_booths' => 'nullable|integer|min:0',
            'type' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:far,upcoming,ongoing,finished,hidden',
            'map_built' => 'sometimes|boolean',
        ];
    }
}
